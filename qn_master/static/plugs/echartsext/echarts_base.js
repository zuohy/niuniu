/**
 *
 * */
//var array = {trigger:'string'}
//console.log(array);


//var test = New echartsEncapsulation();
//test.Init();


function echartsBase(){}
var echartsPrototype = echartsBase.prototype
echartsPrototype.params = {
    title:{},
    tooltip:{},
    toolbox:{},
    legend:{},
    xAxis:{},
    yAxis:{},
    series:{}
}

//暂时不使用
echartsPrototype.init = function(){
    echartsPrototype.title();
    echartsPrototype.tooltip();
    echartsPrototype.toolbox();
    echartsPrototype.legend();
    echartsPrototype.xAxis();
    echartsPrototype.yAxis();
    echartsPrototype.series();
}
echartsPrototype.reback = function(){
    return echartsPrototype.params
}
//title
echartsPrototype.title = function(data){
    echartsPrototype.params.title = data;
}
//tooltip
echartsPrototype.tooltip = function(data){
    echartsPrototype.params.tooltip = data;
}
//toolbox
echartsPrototype.toolbox = function(data){
    echartsPrototype.params.toolbox = data;
}
//legend
echartsPrototype.legend = function(data){
    echartsPrototype.params.legend = data;
}
//xAxis
echartsPrototype.xAxis = function(data){
    echartsPrototype.params.xAxis = data;
}
//yAxis
echartsPrototype.yAxis = function(data){
    echartsPrototype.params.yAxis = data;
}
//series
echartsPrototype.series = function(data){
    echartsPrototype.params.series = data;
}

/******************************************/
function echartsToolboxFeature(){
    return echartsToolboxFeature.prototype.param
}
echartsToolboxFeature.prototype.param = {
    dataView:{
        show:true,
        readOnly:false
    },
    magicType:{
        show:true,
        type:'line',
    },
    restore:{
        show:true,
    },
    saveAsImage:{
        show:true,
    },
    dataZoom:{}

}
/********************************************************/

/********************************************************/
function echartsTooltitle(data){
    switch (data){
        case 'axisPointer':
            return echartsTooltitle.prototype.axisPointer_param;
    }
    return echartsTooltitle.prototype.param
}
echartsTooltitle.prototype.axisPointer_param = {
    type: 'line'
}
/********************************************************/

/********************************************************/
function echartsLegendData(data){
    echartsLegendData.prototype.param = data;
    return echartsLegendData.prototype.param
}
echartsLegendData.prototype.param = []
/********************************************************/

/********************************************************/
/********************************************************/


