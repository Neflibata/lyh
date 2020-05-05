

// document.write('<script type="text/javascript" src="../../../../../public/static/js/balabala/echarts.min.js">');

;!function (window) {
    // https://www.echartsjs.com/examples/zh/editor.html?c=bar-simple
    // barData: {
    //     Mon: 13253,
    //         Tue: 34235,
    //         Wed: 26321,
    //         Thu: 12340,
    //         Fri: 24643,
    //         Sat: 1322,
    //         Sun: 1324
    // },
    Vue.component("chartbar", {
        props: {
            value: {
                type:Object,
                default:function () {
                    return  {
                            Mon: 13253,
                            Tue: 34235,
                            Wed: 26321,
                            Thu: 12340,
                            Fri: 24643,
                            Sat: 1322,
                            Sun: 1324
                    }
                }
            },
            text: String,
            subtext: String,
            color: {
                type: String,
                default: function () {
                    return "#1F9AF9";
                }
            }
        },
        data: function () {
            return {
                dom: null
            }
        },
        methods: {
            barColor: function () {
                var colorList = [
                    '#C1232B', '#B5C334', '#FCCE10', '#E87C25', '#27727B',
                    '#FE8463', '#9BCA63', '#FAD860', '#F3A43B', '#60C0DD',
                    '#D7504B', '#C6E579', '#F4E001', '#F0805A', '#26C0C0',
                    '#FFB7DD', '#660077', '#FFCCCC', '#FFC8B4', '#550088',
                    '#FFFFBB', '#FFAA33', '#99FFFF', '#CC00CC', '#FF77FF',
                    '#CC00CC', '#C63300', '#F4E001', '#9955FF', '#66FF66',
                    '#C1232B', '#B5C334', '#FCCE10', '#E87C25', '#27727B',
                    '#FE8463', '#9BCA63', '#FAD860', '#F3A43B', '#60C0DD',
                    '#D7504B', '#C6E579', '#F4E001', '#F0805A', '#26C0C0',
                    '#FFB7DD', '#660077', '#FFCCCC', '#FFC8B4', '#550088',
                    '#FFFFBB', '#FFAA33', '#99FFFF', '#CC00CC', '#FF77FF',
                    '#CC00CC', '#C63300', '#F4E001', '#9955FF', '#66FF66'
                ];
                //console.log(params);
                console.log(xAxisVersion.reverse());
                var version_arr = xAxisVersion.reverse();
                var unique_arr = xAxisVersion.unique();
                var color_arr = [];
                console.log(unique_arr);
                var cur = -1;


                for (var i = 0; i < version_arr.length; i++) {
                    cur = -1;
                    for (var j = 0; j < unique_arr.length; j++) {
                        if (version_arr[i] === unique_arr[j]) {
                            //console.log(version_arr[i],unique_arr[j]);
                            //console.log(i,j);
                            cur = j;
                            break;

                        }
                    }

                    if (cur >= 0) {
                        color_arr.push(colorList[cur]);
                        //console.log('==='+colorList[cur]);
                    } else {
                        color_arr[i] = "#f00";
                    }
                }

                color_arr = color_arr.reverse();
                return color_arr;
            },
            init: function () {
                var that = this;
                this.$nextTick(function () {
                    var xAxisData = Object.keys(this.value);
                    var seriesData = Object.values(this.value);
                    var option = {
                        title: {
                            text: this.text,
                            subtext: this.subtext,
                            x: 'center'
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                                type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            data: xAxisData
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: [{
                            data: seriesData,
                            type: 'bar',
                            itemStyle: {
                                //通常情况下：
                                normal: {
                                    // 每个柱子的颜色即为colorList数组里的每一项，如果柱子数目多于colorList的长度，则柱子颜色循环使用该数组
                                    color: this.color
                                }
                            }
                        }]
                    };
                    this.dom = echarts.init(this.$refs.dom, 'tdTheme');
                    this.dom.setOption(option);
                    // on(window, 'resize', this.resize)
                })
            }

        },
        template: '<div ref="dom" class="charts chart-bar" ></div>',
        mounted: function () {
            this.init();
        }
    });
    //https://www.echartsjs.com/examples/zh/editor.html?c=pie-simple
    // pieData: [
    //     { value: 335, name: '直接访问' },
    //     { value: 310, name: '邮件营销' },
    //     { value: 234, name: '联盟广告' },
    //     { value: 135, name: '视频广告' },
    //     { value: 1548, name: '搜索引擎' }
    // ],
    Vue.component("chartpie", {
        props: {
            value: {
                type:Array,
                default:function(){
                    return [
                        { value: 335, name: '直接访问' },
                        { value: 310, name: '邮件营销' },
                        { value: 234, name: '联盟广告' },
                        { value: 135, name: '视频广告' },
                        { value: 1548, name: '搜索引擎' }
                    ]
                }
            },
            text: String,
            subtext: String
        },
        data: function () {
            return {
                dom: null
            }
        },
        methods: {
            init: function () {
                this.$nextTick(function () {
                    var legend = this.value.map(x => x.name);
                    console.log(legend);
                    var option = {
                        title: {
                            text: this.text,
                            subtext: this.subtext,
                            x: 'center'
                        },
                        tooltip: {
                            trigger: 'item',
                            formatter: '{a} <br/>{b} : {c} ({d}%)'
                        },
                        legend: {
                            orient: 'vertical',
                            left: 'left',
                            data: legend
                        },
                        series: [
                            {
                                name:"饼状图",
                                type: 'pie',
                                radius: '55%',
                                center: ['50%', '60%'],
                                data: this.value,
                                itemStyle: {
                                    emphasis: {
                                        shadowBlur: 10,
                                        shadowOffsetX: 0,
                                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                                    }
                                }
                            }
                        ]
                    };
                    this.dom = echarts.init(this.$refs.dom, 'tdTheme');
                    console.log(this.dom);
                    this.dom.setOption(option)
                })
            }
        },
        template: '<div ref="dom" class="charts chart-bar" ></div>',
        mounted: function () {
            this.init();
        }
    });
    //https://www.echartsjs.com/examples/zh/editor.html?c=line-stack
    // line:[
    //     {
    //         name:'邮件营销',
    //         type:'line',
    //         stack: '总量',
    //         data:[120, 132, 101, 134, 90, 230, 210]
    //     },
    //     {
    //         name:'联盟广告',
    //         type:'line',
    //         stack: '总量',
    //         data:[220, 182, 191, 234, 290, 330, 310]
    //     },
    //     {
    //         name:'视频广告',
    //         type:'line',
    //         stack: '总量',
    //         data:[150, 232, 201, 154, 190, 330, 410]
    //     },
    //     {
    //         name:'直接访问',
    //         type:'line',
    //         stack: '总量',
    //         data:[320, 332, 301, 334, 390, 330, 320]
    //     },
    //     {
    //         name:'搜索引擎',
    //         type:'line',
    //         stack: '总量',
    //         data:[820, 932, 901, 934, 1290, 1330, 1320]
    //     }
    // ],
    Vue.component("chartline", {
        props: {
            value: {
                type:Array,
                default:function(){
                    return [
                        {
                            name:'邮件营销',
                            type:'line',
                            stack: '总量',
                            data:[120, 132, 101, 134, 90, 230, 210]
                        },
                        {
                            name:'联盟广告',
                            type:'line',
                            stack: '总量',
                            data:[220, 182, 191, 234, 290, 330, 310]
                        },
                        {
                            name:'视频广告',
                            type:'line',
                            stack: '总量',
                            data:[150, 232, 201, 154, 190, 330, 410]
                        },
                        {
                            name:'直接访问',
                            type:'line',
                            stack: '总量',
                            data:[320, 332, 301, 334, 390, 330, 320]
                        },
                        {
                            name:'搜索引擎',
                            type:'line',
                            stack: '总量',
                            data:[820, 932, 901, 934, 1290, 1330, 1320]
                        }
                    ]
                }
            },
            text: String,
            subtext: String,
            xData:{
                type:Array,
                default:function(){
                    return ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
                }
            }
        },
        data: function () {
            return {
                dom: null
            }
        },
        methods: {
            init: function () {
                this.$nextTick(function () {
                    // debugger;
                    var legend = this.value.map(x => x.name);
                    var option = {
                        title: {
                            text: this.text
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                                type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                            }
                        },
                        legend: {
                            data: legend
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        toolbox: {
                            // feature: {
                            //     saveAsImage: {}
                            // }
                        },
                        xAxis: {
                            type: 'category',
                            boundaryGap: false,
                            data: this.xData
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: this.value
                    };
                    this.dom = echarts.init(this.$refs.dom);
                    this.dom.setOption(option, true);
                })
            }
        },
        template: '<div ref="dom" class="charts chart-bar" ></div>',
        mounted: function () {
            this.init();
        }
    });

// i:[
//     {
//     name:"新增用户",
//     value:"33",
//     iconfont:"icon-share",
//     color:"#f3bb45",
//     unit:"人"
//     }, {
//         name:"新增用户",
//         value:"332",
//         iconfont:"icon-share",
//         color:"#f3bb45",
//         unit:"人"
//     }, {
//         name:"新增用户",
//         value:"313",
//         iconfont:"icon-share",
//         color:"#7ac29a",
//         unit:"人"
//     }, {
//         name:"新增用户",
//         value:"133",
//         iconfont:"icon-share",
//         color:"black",
//         unit:"人"
//     }, {
//         name:"新增用户",
//         value:"133",
//         iconfont:"icon-share",
//         color:"black",
//         unit:"人"
//     }
// ],
    Vue.component("chartcard", {
        props: {
            value :{
                type:Array,
                default:function(){
                    return [
                        {
                            name:"新增用户",
                            value:"33",
                            iconfont:"icon-share",
                            color:"#f3bb45",
                            unit:"人"
                        }, {
                            name:"新增用户",
                            value:"332",
                            iconfont:"icon-search1",
                            color:"#00FFF2",
                            unit:"人"
                        }, {
                            name:"新增用户",
                            value:"313",
                            iconfont:"icon-friend",
                            color:"#7ac29a",
                            unit:"人"
                        }
                    ]
                }
            }
        },
        data: function(){
            var num = this.value.length;
            return {
                width:{width:'calc(100% / '+num+' - 20px)'}
            };
        },
        methods: {
            init: function () {
                var num = this.value.length;
                this.width={width:'calc(100% / '+num+' - 20px)'};
                for(var i = 0; i < this.value.length; i++)
                {
                    var demo = new CountUp("ddc"+i, 0, this.value[i]["value"], 0, 2.5);
                    demo.start();
                }
            }
        },
        template: '<div class="div-flex">' +
                    '<template v-for="(item, index) in value">'+
                    '<div class="flex" :style="width">' +
                        '<div class="iconfont" :class="\'iconfont\' in item?item.iconfont:\'icon-xihuan1\'" :style="{background:\'color\' in item?item.color:\'#2db7f5\'}"></div>' +
                        '<div class="text" >' +
                            '<p>{{item.name}}</p>' +
                            '<h3><span :id="\'ddc\'+index">{{item.num}}</span>{{item.unit}}</h3>' +
                        '</div>' +
                    '</div>' +
                    '</template>'+
                 '</div>',
        mounted: function () {
               this.init();
        }
    });

    Vue.component("iconselect", {
        props: {
            icon:{
                type:Array,
                default:function(){
                    return []
                }
            },
            "selectIcon":{
                type:String,
                default:""
            }
        },
        data:function(){
            return {
                "selectIndex":0
            }
        },
        methods: {
            init:function(){
                var index = this.icon.indexOf(this.selectIcon);
                if(index > -1)
                    this.selectIndex = index;
                else
                    this.selectIndex = 0;
            },
            changeIcon:function(index){
                if(index == this.selectIndex)
                    return;
                else
                {
                    this.selectIndex = index;
                    this.$emit('on-change', this.icon[index]);
                }
            }
        },
        template:' <ul class="icon-select-ul">' +
        '            <template v-for="(item, index) in icon">' +
        '                <li class="iconfont" :class="[item, index==selectIndex?\'on\':\'\']" @click="changeIcon(index)"></li>' +
        '            </template>       '+
        '</ul>',
        mounted: function () {
            this.init();
        }
    });
    Vue.component("chartdistribute", {
        props: {
            chartName:{
                type:String,
                default:"chartpie"
            },
            chartData:{
                type:Object
            }

        },
        provide :function() {
            return {
                chartdistribute: this
            }
        },
        data:function(){
            return {
            }
        },
        inject: ['chartmodel'],
        methods: {
            ccc:function(){
                console.log(this.chartData)
            }
        },
        render:function(h){
            var that = this;
            return h(this.chartName, {
                class:{
                    border_shadow:true
                },
                props:function () {
                    var obj = {};
                    // debugger;
                    if(that.chartData.data instanceof Array && that.chartData.data.length > 0)
                        obj.value = that.chartData.data;
                    else if(that.chartData.data instanceof Object && Object.keys(that.chartData.data).length > 0)
                        obj.value = that.chartData.data;

                    if("text" in that.chartData)
                        obj.text = that.chartData.text;
                    if("color" in that.chartData)
                        obj.color = that.chartData.color;
                    if("xData" in that.chartData)
                        obj.xData = that.chartData.xData;
                    return obj;
                }(),
                ref:"chartcard"
            });
//            return h('button',{
//                on:{
//                    click:this.ccc
//                }
//            }, "点击")
        },
        updated: function () {
            this.$refs.chartcard.init();
            console.log(1);
        }
    });
    Vue.component("chartconfig", {
        props: {
            choice:{
                type:Object,
                default:{}
            },
            key_1:Number,
            key_2:Number,
            beChoice:{
                type:String,
                default:""
            }
        },
        provide :function() {
            return {
                chartconfig: this
            }
        },
        data:function(){
            return {
                cs: this.beChoice,
                width_1: "100px",
                dataNum: 1,
                chickChoice: {}, //表的总模型
                chickTable: {},//条件语句
                chickModel: {},//表模型,用于添加新的查询语句的使用用的
                on_off: false,
                chartName: "",
                num: 1,
                matchList: {
                    '&lt;': '<',
                    '&gt;': '>',
                    '&amp;': '&',
                    '&#34;': '"',
                    '&quot;': '"',
                    '&#39;': "'"

                }
            }
        },
        inject: ['chartmodel', "effect"],
        methods: {
            changeChart:function(data){
                this.cs = data;
                this.effect.chartName = this.chartName = data;
                this.chickChoice = this.choice[data];
                this.chickTable = this.choice[data]["chickTable"];
                this.chickModel = this.choice[data]["chickModel"];

                console.log(this.choice[data]["chickTable"], "ddd");
                this.chartmodel.changeData(this.key_1, this.key_2, "beChoice", data);
            },
            testChart:function(){
                if(this.chartName == "")
                {
                    this.$Message.error("请先选择图标类型");
                    return ;
                }
                if(this.chickTable.length < 1)
                {
                    this.$Message.error("请先添加配置参数");
                    return ;
                }
                this.chickChoice["chickTable"] = this.chickTable;
                console.log(this.chickChoice);
                this.chartmodel.testChart(this.key_1, this.key_2, this.chartName, this.chickChoice);
            },
            deepCopy:function deepClone(target) {  //深拷贝
                // 定义一个变量
                var result;
                // 如果当前需要深拷贝的是一个对象的话
                if (typeof target === 'object') {
                    // 如果是一个数组的话
                    if (Array.isArray(target)) {
                        result = []; // 将result赋值为一个数组，并且执行遍历
                        for (var i in target) {
                            // 递归克隆数组中的每一项
                            result.push(deepClone(target[i]))
                        }
                        // 判断如果当前的值是null的话；直接赋值为null
                    } else if(target===null) {
                        result = null;
                        // 判断如果当前的值是一个RegExp对象的话，直接赋值
                    } else if(target.constructor===RegExp){
                        result = target;
                    }else {
                        // 否则是普通对象，直接for in循环，递归赋值对象的所有值
                        result = {};
                        for (var i in target) {
                            result[i] = deepClone(target[i]);
                        }
                    }
                    // 如果不是对象的话，就是基本数据类型，那么直接赋值
                } else {
                    result = target;
                }
                // 返回最终结果
                return result;
            },
            //统一的回调函数
            changeTable:function(selectData, index, type){
                console.log(selectData, index, type, this.chickChoice);
                switch(type)
                {
                    case "tableName": this.$set(this.chickTable[index],"tableName", selectData["label"]);break;
                    case "selectType":this.$set(this.chickTable[index]["field"],"selectType", selectData["label"]);break;
                    case "selectField":this.$set(this.chickTable[index]["field"],"selectField", selectData["label"]);break;
                    case "timeField":this.$set(this.chickTable[index]["field"],"timeField", selectData["label"]);break;
                    case "selectFieldTimeCycle":this.$set(this.chickChoice,"timeCycle", selectData["value"]);break;
                    case "selectFieldTimeCycleType":this.$set(this.chickChoice,"showTimeCycleType", selectData["value"]);break;
                    case "whereFieldAdd1":
                        if(index instanceof Array)
                            this.$set(this.chickTable[index[0]]["where"][index[1]], 0, selectData["value"]);
                        else
                            this.$Message.error("数据传入错误");break;
                    case "whereFieldAdd2":
                        if(index instanceof Array)
                            this.$set(this.chickTable[index[0]]["where"][index[1]], 1, selectData);
                        else
                            this.$Message.error("数据传入错误");break;
                    case "name":this.$set(this.chickTable[index],"name", selectData);break;
                    case "unit":this.$set(this.chickTable[index],"unit", selectData);break;
                    case "text":this.$set(this.chickChoice,"text", selectData);break;
                    case "choiceColor":this.$set(this.chickChoice,"color", selectData);break;
                    case "color":this.$set(this.chickTable[index],"color", selectData);break;
                    case "whereDel":this.$delete(this.chickTable[index]["where"],selectData);break;
                    case "colAdd":
                        //var model = Object.assign({},this.chickModel, this.$options.data().Form);  深拷贝(只有最外层深拷贝了)
                        this.chickTable.push(this.deepCopy(this.chickModel));break;
                    case "colDel":
                        this.$delete(this.chickTable,index);
                        break;
                    case "whereAdd":
                        if( selectData in this.chickTable[index]["where"] )
                            this.$Message.error("该字段已经添加");
                        else
//                            this.chickTable[index]["where"][selectData] = ["", ""];
                            this.$set(this.chickTable[index]["where"],selectData, ["", ""]);
                        console.log(this.chickTable[index]["where"]);
                        console.log(this.chickTable[index]["where"].length);
                       break;
                    case "iconfont":this.$set(this.chickTable[index],"iconfont", selectData);break;
                }
                console.log(this.chickTable)

            },
            init: function () {
                if(this.cs != ""){
                    this.effect.chartName = this.chartName = this.cs;
                    this.chickChoice = this.choice[this.cs];
                    this.chickTable = this.choice[this.cs]["chickTable"];
                    this.chickModel = this.choice[this.cs]["chickModel"];
                }

            },
            changeOnOff:function(){
                this.on_off = !this.on_off;
            },
            addWhere:function(index, filed){
                //console.log(index, filed);
            },
            changeIndex:function(key1, data)
            {
                if(data)
                    this.chartmodel.changeZIndex(key1);
                else
                    this.chartmodel.changeZIndex(9);
            }
        },
        render:function(h){
            var that = this;
            return h('div',{
                class:{
                    "config_div":true
                }
            },
                (function(){
                    var turn_on = [];
                    turn_on.push(h("em", that.effect.key_1+"."+that.effect.key_2));

                    turn_on.push(h("div",{
                            props:{
                                id:that.cs
                            },
                            class:{
                                "title-flex":true
                            }
                        },  [
                            h("span", "图表类型："),
                            h("i-select",{
                                    props:{
                                        value:that.cs
                                    },
                                    on:{
                                        "on-change":that.changeChart
                                    }
                                },
                                (function(){
                                    var a = [];
                                    for(i in that.choice)
                                    {
                                        a.push(h("i-option",{
                                            props:{
                                                value:i,
                                                key: i,
                                            }
                                        }, that.choice[i].name))
                                    }
                                    return a;
                                })()),
                            h("i-button", {
                                on:{
                                    click:that.changeOnOff
                                }
                            }, "配置参数"),
                            h('i-button', {
                                on:{
                                    click:that.testChart
                                }
                            }, "更新数据")

                        ]));
                    //翻转层扩展

                    if("text" in that.chickChoice)
                    {
                        turn_on.push(h('div',{
                            class:{
                                "div-input":true
                            }
                        }, [
                            h("span", "标题："),
                            h("i-input", {
                                style:{
                                    width:"auto"
                                },
                                props:{
                                    value:that.chickChoice["text"]
                                },
                                on:{
                                    "on-change": function(event){
                                        that.changeTable(event.target.value, that.chartName, "text")
                                    }
                                }
                            })
                        ]));
                    }
                    if("color" in that.chickChoice)
                    {
                        turn_on.push(h('div',{
                            class:{
                                "div-input":true
                            }
                        }, [
                            h("span", "颜色选择："),
                            h('Color-Picker',{
                                style:{
                                    width:"auto"
                                },
                                props:{
                                    value:that.chickChoice["color"]
                                },
                                on:{
                                    "on-change":function(index){
                                        return function(data){
                                            that.changeTable(data, that.chartName, "choiceColor")
                                        }

                                    }(i),
                                    "on-open-change":function (index) {
                                        return function(data)
                                        {
                                            that.changeIndex(index, data)
                                        }
                                    }(that.key_1)
                                }
                            })
                        ]));
                    }
                    if("timeCycle" in that.chickChoice)
                    {
                        turn_on.push(h('div',{
                            class:{
                                "div-input":true
                            }
                        }, [
                            h('span', "时间周期:"),
                            h("i-select", {
                                props:{
                                    value:that.chickChoice["timeCycle"],
                                    "label-in-value":true
                                },
                                style:{
                                    width:'auto'
                                },
                                on:{
                                    "on-change": function(index){
                                        return function(data){
                                            that.changeTable(data, index, "selectFieldTimeCycle")
                                        }
                                    }(i)
                                }
                            }, (function(){
                                var option = [];
                                var tableFieldTimeCycle = [{value:"0", label:"不开启"}, {value:"7", label:"7天"}, {value:"14", label:"14天"}];
                                for(var index in tableFieldTimeCycle)
                                {
                                    option.push(h('i-option',{
                                        props:{
                                            value: tableFieldTimeCycle[index].value,
                                            key:  tableFieldTimeCycle[index].value,
                                            label:tableFieldTimeCycle[index].label
                                        }
                                    }, tableFieldTimeCycle[index]["label"]))
                                }
                                return option;
                            }()))
                        ]));
                        if("0" !== that.chickChoice["timeCycle"] && "showTimeCycleType" in that.chickChoice)
                        {
                            turn_on.push(h('div',{
                                class:{
                                    "div-input":true
                                }
                            }, [
                                h('span', "显示类别:"),
                                h("i-select", {
                                    props:{
                                        value:that.chickChoice["showTimeCycleType"],
                                        "label-in-value":true
                                    },
                                    style:{
                                        width:'auto'
                                    },
                                    on:{
                                        "on-change": function(index){
                                            return function(data){
                                                that.changeTable(data, index, "selectFieldTimeCycleType")
                                            }
                                        }(i)
                                    }
                                }, (function(){
                                    var option = [];
                                    var tableFieldTimeCycleType = [{value:"week", label:"星期"}, {value:"date", label:"日期"}];
                                    for(var index in tableFieldTimeCycleType)
                                    {
                                        option.push(h('i-option',{
                                            props:{
                                                value: tableFieldTimeCycleType[index].value,
                                                key:  tableFieldTimeCycleType[index].value,
                                                label:tableFieldTimeCycleType[index].label
                                            }
                                        }, tableFieldTimeCycleType[index]["label"]))
                                    }
                                    return option;
                                }()))
                            ]));
                        }
                    }

                    turn_on.push(h('div'));
                    turn_on.push(
                        h('Drawer',{
                            props:{
                                //title:that.effect.key_1+"."+that.effect.key_2+"区域，配置参数",
                                value:that.on_off,
                                width:"50%"
                            },
                            on:{
                                input:function(data){
//                                    console.log(event);
                                    that.on_off = data;
                                }
                            }
                        }, [
                            h('div', {
                                class:{
                                    "div-list":true
                                }
                            },[
                                h("div", {
                                    class:{
                                        "button-list":true
                                    }
                                }, [
                                    // h("h3","标题"),
                                    h('i-button',{
                                        on:{
                                            click:function(){
                                                that.changeTable("","","colAdd");
                                            }
                                        }
                                    }, "添加一条"),
                                    // h('i-button', "这是个按钮"),
                                    // h('i-button', "这是个按钮")
                                ]),
                                h("div", {
                                    class:{
                                        "div-ul":true
                                    }
                                },(
                                    function(){
                                        var root = [];
                                        for(var i in that.chickTable)
                                        {
                                            var root_i = [];
                                            root_i.push(h('em',{
                                                on:{
                                                    click:function (index) {
                                                        return function(){
                                                            console.log(1);
                                                            that.changeTable("", index, "colDel");
                                                        }
                                                    }(i)
                                                }
                                            },"X"));
                                            for(var field in that.chickTable[i])
                                            {
                                                //抽屉层扩展
                                                switch (field)
                                                {
                                                    case "tableName": root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        }, [
//                                                    h("span", that.chickTable[i]["tableName"]),
                                                            h('span', "表名:"),
                                                            h('i-select', {
                                                                props:{
                                                                    value:that.chickTable[i]["tableName"],
                                                                    "label-in-value":true,
                                                                },
                                                                style:{
                                                                    width:'auto'
                                                                },
                                                                on:{
                                                                    "on-change": function(index){
                                                                        return function(data){
                                                                            that.changeTable(data, index, "tableName")
                                                                        }
                                                                    }(i)
                                                                }
                                                            }, (
                                                                function(){
                                                                    var option = [];
                                                                    for(var tableName in that.chartmodel.databaseconfig)
                                                                    {
                                                                        option.push(h('i-option',{
                                                                            props:{
                                                                                value:tableName,
                                                                                key: tableName
                                                                            }
                                                                        }, tableName))
                                                                    }
                                                                    return option;
                                                                })())
                                                        ])); break;
                                                    case "field":root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        },[
                                                            h('span', "类型:"),
                                                            h("i-select", {
                                                                props:{
                                                                    value:that.chickTable[i]["field"]["selectType"],
                                                                    "label-in-value":true
                                                                },
                                                                style:{
                                                                    width:'auto'
                                                                },
                                                                on:{
                                                                    "on-change": function(index){
                                                                        return function(data){
                                                                            that.changeTable(data, index, "selectType")
                                                                        }
                                                                    }(i)
                                                                }
                                                            }, (function(){
                                                                var option = [];
                                                                for(var tableName in that.chickTable[i]["field"]["options"])
                                                                {
                                                                    option.push(h('i-option',{
                                                                        props:{
                                                                            value:that.chickTable[i]["field"]["options"][tableName],
                                                                            key: that.chickTable[i]["field"]["options"][tableName]
                                                                        }
                                                                    }, that.chickTable[i]["field"]["options"][tableName]))
                                                                }
                                                                return option;
                                                            }())),
                                                            (function(){
                                                                var slectF = [];
                                                                if(that.chickTable[i]["field"]["selectType"] == "求和")
                                                                {
                                                                    slectF.push(h('span', "求和字段:"))
                                                                    slectF.push(h("i-select", {
                                                                        props:{
                                                                            value:that.chickTable[i]["field"]["selectField"],
                                                                            "label-in-value":true
                                                                        },
                                                                        style:{
                                                                            width:'auto'
                                                                        },
                                                                        on:{
                                                                            "on-change": function(index){
                                                                                return function(data){
                                                                                    that.changeTable(data, index, "selectField")
                                                                                }
                                                                            }(i)
                                                                        }
                                                                    }, (function(){
                                                                        var option = [];
                                                                        var tableField = that.chartmodel.databaseconfig[that.chickTable[i]["tableName"]];
                                                                        for(var index in tableField)
                                                                        {
                                                                            option.push(h('i-option',{
                                                                                props:{
                                                                                    value: tableField[index]["key"],
                                                                                    key:  tableField[index]["key"]
                                                                                }
                                                                            }, tableField[index]["key"]))
                                                                        }
                                                                        return option;
                                                                    }())))
                                                                }
                                                                // debugger;
                                                                // console.log(that.chickChoice);
                                                                if( "timeCycle" in that.chickChoice && "0" !== that.chickChoice["timeCycle"] )
                                                                {
                                                                    slectF.push(h('span', "时间字段:"))
                                                                    slectF.push(h("i-select", {
                                                                        props:{
                                                                            value:that.chickTable[i]["field"]["timeField"],
                                                                            "label-in-value":true
                                                                        },
                                                                        style:{
                                                                            width:'auto'
                                                                        },
                                                                        on:{
                                                                            "on-change": function(index){
                                                                                return function(data){
                                                                                    that.changeTable(data, index, "timeField")
                                                                                }
                                                                            }(i)
                                                                        }
                                                                    }, (function(){
                                                                        var option = [];
                                                                        var tableField = that.chartmodel.databaseconfig[that.chickTable[i]["tableName"]];
                                                                        for(var index in tableField)
                                                                        {
                                                                            option.push(h('i-option',{
                                                                                props:{
                                                                                    value: tableField[index]["key"],
                                                                                    key:  tableField[index]["key"]
                                                                                }
                                                                            }, tableField[index]["key"]))
                                                                        }
                                                                        return option;
                                                                    }())))
                                                                }

                                                                return  slectF;
                                                            })()
                                                        ])
                                                    );break;
                                                    case "name":root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        },[
                                                            h('span', "名字:"),
                                                            h('i-input',{
                                                                style:{
                                                                    width:"auto"
                                                                },
                                                                props:{
                                                                    value:that.chickTable[i]["name"]
                                                                },
                                                                on:{
                                                                    "on-change":function(index){
                                                                        return function(event){
                                                                            that.changeTable(event.target.value, index, "name")
                                                                        }

                                                                    }(i)
                                                                }
                                                            })
                                                        ])
                                                    );break;
                                                    case "where":root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        },[
                                                            h('span', "条件:"),
                                                            (function(){
                                                                var slectF = [];
                                                                var choiceFiled = "";
                                                                slectF.push(h('span', "选择字段:"));
                                                                slectF.push(h("i-select", {
                                                                    ref:"resetSelect"+ i,
                                                                    props:{
                                                                        value:choiceFiled,
                                                                        "label-in-value":true,
                                                                        clearable:true
                                                                    },
                                                                    style:{
                                                                        width:'auto'
                                                                    },
                                                                    on:{
                                                                        "on-change": function(data){
                                                                            console.log(data);
                                                                            if(typeof(data)  != "undefined")
                                                                                choiceFiled = data.value;
                                                                        }
                                                                    }
                                                                }, (function(){
                                                                    var option = [];
                                                                    var tableField = that.chartmodel.databaseconfig[that.chickTable[i]["tableName"]];
                                                                    for(var index in tableField)
                                                                    {
                                                                        option.push(h('i-option',{
                                                                            props:{
                                                                                value: tableField[index]["key"],
                                                                                key:  tableField[index]["key"],
                                                                                label :tableField[index]["key"]
                                                                            }
                                                                        }, tableField[index]["key"]))
                                                                    }
                                                                    return option;
                                                                }())));
                                                                slectF.push(h('i-button', {
                                                                    on:{
                                                                        click: function(index){
                                                                            return function(){
                                                                                if(choiceFiled != "")
                                                                                {
                                                                                    that.$refs["resetSelect"+index].clearSingleSelect();
                                                                                    that.changeTable(choiceFiled, index, "whereAdd");
                                                                                }

                                                                            }
                                                                        }(i)
                                                                    },
                                                                    props:{
                                                                        type:"info"
                                                                    },
                                                                    style:{
                                                                        "margin-left":"20px"
                                                                    }
                                                                }, "添加一条数据"));
                                                                var selectFD = [];
                                                                for(var whereField in that.chickTable[i]["where"])
                                                                {

                                                                    selectFD.push(h('div', {
                                                                        class:{
                                                                            "div-add":true
                                                                        }
                                                                    },[
                                                                        h('span', "字段:"),
                                                                        h('span', whereField),
                                                                        h('span', "条件:"),
                                                                        h("i-select", {
                                                                            props:{
                                                                                value:that.chickTable[i]["where"][whereField][0],
                                                                                "label-in-value":true
                                                                            },
                                                                            style:{
                                                                                width:'auto'
                                                                            },
                                                                            on:{
                                                                                "on-change": function(index, whereFieldIndex){
                                                                                    return function(data){
                                                                                        that.changeTable(data, [index,whereFieldIndex] , "whereFieldAdd1")
                                                                                    }
                                                                                }(i, whereField)
                                                                            }
                                                                        }, (function(){
                                                                            var option = [];
                                                                            var whereType = [{value:"GT", label:">"}, {value:"LT", label:"<"}, {value:"EGT", label:">="}, {value:"ELT", label:"<="}, {value:"EQ", label:"="}, {value:"NEQ", label:"!="}, {value:"LIKE", label:"LIKE"}, {value:"BETWEEN", label:"BETWEEN"}, {value:"NOT BETWEEN", label:"NOT BETWEEN"}];
                                                                            for(var index in whereType)
                                                                            {
                                                                                option.push(h('i-option',{
                                                                                    props:{
                                                                                        value: whereType[index].value,
                                                                                        key:  whereType[index].value,
                                                                                        label:whereType[index].label
                                                                                    }
                                                                                }, whereType[index]))
                                                                            }
                                                                            return option;
                                                                        }())),
                                                                        h('span', "值:"),
                                                                        h('i-input',{
                                                                            style:{
                                                                                width:'auto'
                                                                            },
                                                                            props:{
                                                                                placeholder:"between数据请用逗号隔开",
                                                                                value:that.chickTable[i]["where"][whereField][1]
                                                                            },
                                                                            on:{
                                                                                "on-change":function(index, whereFieldIndex){
                                                                                    return function(event){
                                                                                        that.changeTable(event.target.value, [index, whereFieldIndex], "whereFieldAdd2")
                                                                                    }
                                                                                }(i, whereField)
                                                                            }
                                                                        }),
                                                                        h('i-button', {
                                                                            props:{
                                                                                type:"error",

                                                                            },
                                                                            style:{
                                                                                "margin-left":"10px"
                                                                            },
                                                                            on:{
                                                                                click:function(index, whereFieldIndex){
                                                                                    return function(){
                                                                                        that.changeTable(whereFieldIndex, index, "whereDel");
                                                                                    }
                                                                                }(i, whereField)
                                                                            }
                                                                        }, "删除")
                                                                    ]))

                                                                }
                                                                slectF.push(selectFD);
                                                                return  slectF;
                                                            })()
                                                        ])
                                                    );break;
                                                    case "color":root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        },[
                                                            h('span', {
                                                                style:{
                                                                    "padding-right":"20px"
                                                                }
                                                            },"颜色选择:"),
                                                            h('Color-Picker',{
                                                                style:{
                                                                    width:"auto"
                                                                },
                                                                props:{
                                                                    value:that.chickTable[i]["color"]
                                                                },
                                                                on:{
                                                                    "on-change":function(index){
                                                                        return function(data){
                                                                            that.changeTable(data, index, "color")
                                                                        }

                                                                    }(i)
                                                                }
                                                            })
                                                        ])
                                                    );break;
                                                    case "iconfont":
                                                        if(that.chartmodel.icon.length < 10)
                                                            break;
                                                        else
                                                        {
                                                            root_i.push(
                                                                h('div',{
                                                                    class:{
                                                                        "div-input":true
                                                                    }
                                                                },[
                                                                    h('span', {
                                                                        style:{
                                                                            "padding-right":"20px"
                                                                        }
                                                                    },"图标选择:"),
                                                                    h('iconselect', {
                                                                        props:{
                                                                            icon:that.chartmodel.icon,
                                                                            "selectIcon":that.chickTable[i]["iconfont"]
                                                                        },
                                                                        on:{
                                                                            "on-change":function(index)
                                                                            {
                                                                                return function(data){
                                                                                    that.changeTable(data, index, "iconfont");
                                                                                }
                                                                            }(i)
                                                                        }
                                                                    })
                                                                ])
                                                            );
                                                        }
                                                        break;
                                                    case "unit":root_i.push(
                                                        h('div',{
                                                            class:{
                                                                "div-input":true
                                                            }
                                                        },[
                                                            h('span', "单位:"),
                                                            h('i-input',{
                                                                style:{
                                                                    width:"auto"
                                                                },
                                                                props:{
                                                                    value:that.chickTable[i]["unit"]
                                                                },
                                                                on:{
                                                                    "on-change":function(index){
                                                                        return function(event){
                                                                            that.changeTable(event.target.value, index, "unit")
                                                                        }

                                                                    }(i)
                                                                }
                                                            })
                                                        ])
                                                    );break;
                                                }
                                            }
                                            root.push(
                                                h('div', {
                                                    class:{
                                                        "div-li":true
                                                    }
                                                }, root_i)
                                            )
                                        }
                                        return root;
                                    })())
                            ])
                        ]));
                    return turn_on;
                })()
            )
        },
        mounted: function () {
            this.init();
            console.log(this.choice)
        }
    });
    Vue.component("effect", {
        props: {
            showchart:{
                type:Boolean,
                default:false
            },
            col:{
                type:Object,
                defalut:{}
            },
            key_1:Number,
            key_2:Number
        },
        provide :function() {
            return {
                effect: this
            }
        },
        data:function(){
            return {
                chartName:""
            }
        },
        inject: ['chartmodel'],
        methods: {
            init: function () {
                // console.log(this.showchart);
                if(this.showchart)
                {
                    this.$nextTick(function () {
                        this.dom = new Swiper(this.$refs.dom,{
                            effect : 'flip',
                            flipEffect: {
                                slideShadows : true,
                                limitRotation : true
                            },
                            mousewheel: true ,
                            noSwiping : true,
                            noSwipingClass : 'stop-swiping'
                        });
                        // this.dom.setOption(option)
                    })
                }
            }
        },
        template:'<div  ref="dom" :class="showchart?\'swiper-container\':\'col-height\'">' +
        '           <div v-if="showchart" class="swiper-wrapper">' +
        '               <div class="swiper-slide col-height">' +
        '                  <chartconfig :choice = col.choice :key_1="key_1" :key_2="key_2" :beChoice="col.beChoice"></chartconfig>' +
        '               </div>' +
        '               <div class="swiper-slide col-height">' +
        '                   <template v-if="col.beChoice">' +
        '                       <chartdistribute :chartName="col.beChoice" :chartData="col.choice[col.beChoice]"></chartdistribute>' +
        '                   </template>' +
        '               </div>' +
        '           </div>' +
        '           <template v-else>' +
        '               <slot name="ccc"></slot>' +
        '           </template>' +
        '       </div>',
        mounted: function () {
            this.init();
        }
    });
    /**
     * showchart表示是否能翻转
     */
    Vue.component("chartmodel",{

        props:{
            statistics:{
                type:Object,
                default: {}
            },
            showchart:{
                type:Boolean,
                default:true
            },
            databaseconfig:{
                type:Object,
                default: function(){
                    return {};
                }
            },
            icon:{
                type:Array,
                default:function(){
                    return [];
                }
            }
        },
        provide :function() {
            return {
                chartmodel: this
            }
        },
        data:function () {
          return {
              z_index_key:9
          }
        },
        template:'<div class="rowBox">' +
        '      <!--        Row 的height 加起来为92%            -->' +
        '             <template v-for="(value, key) in statistics.model">' +
        '                   <Row :style="{height: value.height, \'z-index\': key==z_index_key?\'9\':\'0\'}">  ' +
        '                        <template  v-for="(v, k) in value.col">' +
        '                             <i-col  :offset="offsetChick(k)"  :sm="v.width" :xs="v.width" :md="v.width" :lg="v.width">' +
        '                                 <template v-if="statistics.debug " >' +
        '                                 <effect :showchart="showchart" :col="v"  :key_1="key" :key_2="k">' +
        '                                 </effect>' +
        '                                 </template >' +
        '                                 <template v-else>' +
        '                                       <chartdistribute :chartName="v.beChoice" :chartData="v.choice[v.beChoice]"></chartdistribute>' +
        '                                 </template>   ' +
        '                             </i-col>    ' +
        '                        </template>    ' +
        '                   </Row> ' +
        '             </template>' +
        '      </div>',
        methods:{
            offsetChick:function(k)
            {
                return k !== 0? 1:0;
            },
            changeData:function(key_1, key_2, changeType, value)
            {
                this.$emit("change-model", key_1, key_2, changeType, value);
            },
            testChart:function(key_1,key_2, changeType, value)
            {
                this.$emit("test-model", key_1, key_2, changeType, value);
            },
            changeZIndex:function(key_1)
            {
                this.z_index_key = key_1;
            }
        },
        mounted:function(){
            console.log(this.statistics);
        }
    });
}(window);

