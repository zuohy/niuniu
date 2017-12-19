/**
 * echarts api
 *
 * */

var echartsApi = function () {
}
var echartsApiP = echartsApi.prototype
/**
 *
 * 条形图 双数据
 * */
echartsApiP.api_singlebar = function (bar_arr, x_arr) {
    var echartsB = new echartsBase()
    var title_arr = {
        text: '',
        subtext: ''
    }
    echartsB.title(title_arr)
    var tooltip_arr = {
        trigger: 'axis',
        axisPointer: new echartsTooltitle('axisPointer'),
    }
    echartsB.tooltip(tooltip_arr)

    //toolbox
    var toolbox_arr = {
        show: false,
        //feature: New echartsToolboxFeature
    }
    echartsB.toolbox(toolbox_arr)

    //legend
    var legend_arr = {
        show: false,
        data: new echartsLegendData(x_arr),

    }
    echartsB.legend(legend_arr)

    //xAxis
    var xAxis_arr = {
        show: true,
        name: '',
        type: 'category',
        data:x_arr,

        axisTick: {
            alignWithLabel: true
        }
    }
    echartsB.xAxis(xAxis_arr)

    var yAxis_arr = [
        {
            type: 'value',
            name: '数量',
            min: 0,
            max: 250,
            interval: 50,
            axisLabel: {
                formatter: '{value} 个'
            },
            boundaryGap:['50%','50%'],
        }
    ]
    echartsB.yAxis(yAxis_arr)


    var series_arr = [
        {
            name: '',
            type: 'bar',
            connectNulls:true,
            data: bar_arr,
            smooth:true,
            tooltip:{
                show:false,
            },
            lineStyle:{
                normal:{
                    color: "#2d5366"
                }
            },

        },

    ]
    echartsB.series(series_arr)
    //var a = echartsB.reback();
    //console.log(a)
    return  echartsB.reback();
}

/**
 * 限时完成、延期完成
 * 条形图 双数据
 * */
echartsApiP.api_doublebar = function (delay_bar_arr,onTime_bar_arr, x_arr) {
    var echartsB = new echartsBase()
    var title_arr = {
        text: '',
        subtext: ''
    }
    echartsB.title(title_arr)
    var tooltip_arr = {
        trigger: 'axis',
        axisPointer: new echartsTooltitle('axisPointer'),
    }
    echartsB.tooltip(tooltip_arr)

    //toolbox
    var toolbox_arr = {
        show: false,
        //feature: New echartsToolboxFeature
    }
    echartsB.toolbox(toolbox_arr)

    //legend
    var legend_arr = {
        show: false,
        data: new echartsLegendData(x_arr),

    }
    echartsB.legend(legend_arr)

    //xAxis
    var xAxis_arr = {
        show: true,
        name: '',
        type: 'category',
        data:x_arr,

        axisTick: {
            alignWithLabel: true
        }
    }
    echartsB.xAxis(xAxis_arr)

    var yAxis_arr = [
        {
            type: 'value',
            name: '数量',
            min: 0,
            max: 250,
            interval: 50,
            axisLabel: {
                formatter: '{value} 个'
            },
            boundaryGap:['50%','50%'],
        }
    ]
    echartsB.yAxis(yAxis_arr)


    var series_arr = [
        {
            name: '限时完成',
            type: 'bar',
            connectNulls:true,
            data: onTime_bar_arr,
            smooth:true,
            tooltip:{
                show:false,
            },
            lineStyle:{
                normal:{
                    color: "#2d5366"
                }
            },

        },
        {
            name: '延期完成',
            type: 'bar',
            connectNulls:true,
            data: delay_bar_arr,
            smooth:true,
            lineStyle:{
                normal:{
                    color: "#ff7828"
                }
            },

        },

    ]
    echartsB.series(series_arr)
    //var a = echartsB.reback();
    //console.log(a)
    return  echartsB.reback();
}

/**
 * 限时完成、延期完成
 * 大饼图
 * */
echartsApiP.api_pie = function (pie_arr,legend_arr) {
    var echartsB = new echartsBase()

    var tooltip_arr = {
        trigger: 'item',
        formatter: "{a} <br/>{b} : {c} ({d}%)",
    }
    echartsB.tooltip(tooltip_arr)

    //xAxis
    var xAxis_arr = {
        show: false,
        name: '',
        type: 'category',
    }
    echartsB.xAxis(xAxis_arr)

    var yAxis_arr = {
        show : false
    }
    echartsB.yAxis(yAxis_arr)

    //toolbox
    var toolbox_arr = {
        show: false,
        //feature: New echartsToolboxFeature
    }
    echartsB.toolbox(toolbox_arr)

    //legend
    var legend_arr = {
        show:false,
        orient: 'vertical',
        left: 'left',
        data: legend_arr

    }
    echartsB.legend(legend_arr)


    var series_arr = [
        {
            name: '访问来源',
            type: 'pie',
            radius : '50%',
            center: ['50%', '50%'],
            data:pie_arr,
            selectedOffset: 5,
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }

        },


    ]
    echartsB.series(series_arr)
    //var a = echartsB.reback();
    //console.log(a)
    return  echartsB.reback();
}



//var test = New echartsApi();
//test.api_persure_rate();
