window.getMovingAverage = function ( dataArray, maLength, weekdaysOnly ) {

	weekdaysOnly = weekdaysOnly || false;
    var denominator;

    // initialize early datapoints
    var avgArray = [];

    // // initialize current sum
    var curSum = 0;
	var curDays = [];
	var curAvg = 0;

	var scoreCeiling = 100; // Limit max score to some value to prevent spikes from adversely affecting the average

    for ( var i = 0; i < dataArray.length; i++ ) {
		dayOfWeek = new Date( dataArray[ i ].x ).getDay();

		if ( weekdaysOnly && (dayOfWeek === 0 || dayOfWeek === 6) ) {
			avgArray[ i ] = {
				x : dataArray[ i ].x,
				y : curAvg
			};
		}
		else {
			curDays.push( Math.min( scoreCeiling, dataArray[ i ].y ) );
			if ( curDays.length > maLength ) {
				curDays.shift(); // shift first element off
			}

			curSum = curDays.reduce(function(p,c) { return p + c; });
			denominator = curDays.length;
			curAvg = curSum / denominator;

			avgArray[ i ] = {
				x : dataArray[ i ].x,
				y : curAvg
			};
		}
    }

    return avgArray;

};







$(document).ready(function(){

	/**
		{
			dailyHits : [
				{
					key : "Series 1",
					values : [
						{ x: timestamp, y: value },
						{ x: timestamp, y: value },
						{ x: timestamp, y: value },
						{ x: timestamp, y: value },
						....
					]
				},
				{ key : "Series 2", ... }
			],
			weeklyLabels : [unixtimestamp-milliseconds, ts, ts, ...],
			monthlyLabels : [unixtimestamp-milliseconds, ts, ts, ...]
		}
	 **/
	function getData () {

		var rawData = JSON.parse( $('#userjourney-data').text() );

		// rawData[0].color = "#FC8383";
		// rawData[1].color = "#D194FF";

		// username = rawData[0].key;

		// rawData.push( {
		// 	key: username + " 7-Day Avg",
		// 	values: getMovingAverage( rawData[0].values, 7 ),
		// 	color: "#FF0000"
		// } );

		// rawData.push( {
		// 	key: "28-Day Moving Average",
		// 	values: getMovingAverage( rawData[0].values, 28 ),
		// 	color: "#FF0000"
		// } );

		// rawData.push( {
		// 	key: username + " 20-Weekday Avg (no weekends)",
		// 	values: getMovingAverage( rawData[0].values, 20, true ),
		// 	color: "#FF8000"
		// } );

		// username = rawData[1].key;

		// rawData.push( {
		// 	key: username + " 7-Day Avg",
		// 	values: getMovingAverage( rawData[1].values, 7 ),
		// 	color: "#0000FF"
		// } );

		// rawData.push( {
		// 	key: "28-Day Moving Average",
		// 	values: getMovingAverage( rawData[1].values, 28 ),
		// 	color: "#FF0000"
		// } );

		// rawData.push( {
		// 	key: username + " 20-Weekday Avg (no weekends)",
		// 	values: getMovingAverage( rawData[1].values, 20, true ),
		// 	color: "#00D5FF"
		// } );

		/* */
		var initialDataLength = rawData.length;
		for (var i = 0; i < initialDataLength; ++i) {
			username = rawData[ i ].key;

			// rawData.push( {
			// 	key: username + " 7-Day Avg",
			// 	values: getMovingAverage( rawData[i].values, 7 ),
			// 	color: "#FF0000"
			// } );

			// rawData.push( {
			// 	key: username + " 28-Day Moving Average",
			// 	values: getMovingAverage( rawData[i].values, 28 ),
			// 	color: "#FF0000"
			// } );

			rawData.push( {
				key: username + " 20-Weekday Avg (no weekends)",
				values: getMovingAverage( rawData[ i ].values, 20, true ),
				// color: "#FF8000"
			} );
		/* */
		}

		rawData.splice(0, initialDataLength); // remove daily score arrays, only show generated averages

		return { dailyHits : rawData };

	}


	nv.addGraph(function() {

		window.hitsData = getData();
		console.log(hitsData);
		window.chart = nv.models.stackedAreaChart()
			// .margin({right: 100})
			// .x(function(d) { return d[0] })   //We can modify the data accessor functions...
			// .y(function(d) { return d[1] })   //...in case your data is formatted differently.
			.useInteractiveGuideline(true)    //Tooltips which show all data points. Very nice!
			// .rightAlignYAxis(true)      //Let's move the y-axis to the right side.
			// .transitionDuration(500)
			.showControls(true)       //Allow user to choose 'Stacked', 'Stream', 'Expanded' mode.
			.clipEdge(true)
		;

		chart._options.controlOptions = ['Stacked', 'Expanded']; // hide 'Stream' view

		chart.xAxis
			.tickFormat(function(d) {
				return d3.time.format('%x')(new Date(d))
			});

		chart.yAxis
			.tickFormat(d3.format(',.0f'));

		d3.select('#userjourney-chart svg')
			.datum( hitsData.dailyHits )
			.attr( "height" , $(window).height() - 100 )
			.transition().duration(500)
			.call(chart);

		// $("#userjourney-chart svg").height( $(window).height() - 100 );

		nv.utils.windowResize(chart.update);

		return chart;
	});
});