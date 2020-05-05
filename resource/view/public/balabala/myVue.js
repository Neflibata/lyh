;!function (window) {
	Vue.component("selfpage",{
        props:{
            pagestart:{
                type:Number,
                default:function(){
                    return 1;
                }
            },
            pagenums:{
                type:Number,
                default:function(){
                    return 20;
                }
            },
            page:{
                type:Number,
                default:function(){
                    return 1;
                }
            },
            pagecount:{
                type:Number
            }
        },
        data:function(){
            return {
                insertpage: this.page
            }
        },
        template:'<div class="page">\
                            <div>\
                                <span @click="pageChange(page-1)">上一页</span>\
                                <template  v-for="n in pagenums" v-if="n>=pagestart" > <span :class="tavcls(n)" @click="pageChange(n)">{{n}}</span></template>\
                                <span @click="pageChange(page+1)">下一页</span>\
                                <span>共{{pagecount}}页</span>\
                                <input type="text" v-model="insertpage" value="insertpage">\
                                <span @click="pageChange(insertpage)">跳转</span>\
                            </div>\
                        </div>',
        methods:{
            tavcls:function(num){
                return {
                    "page-on": num === this.page
                }
            },
            pageChange:function(num){
                num = Number(num);
                num = num <=1? 1:num;
                num = num >=this.pagecount? this.pagecount: num;
                this.$emit("changepage", num);
                //this.$emit("on-click", num);
            }
        }
    });
    Vue.component("drawerbox",{
        props:{
            //未完善，暂时不用
        },
        data:function(){
            return {
                cl:false
            }
        },
        template:'<div style="position: absolute;right: 0;top: 0;overflow: hidden;"> ' +
                    '<div class="drawerbox " :class="cl?\'on\':\'\'">' +
                    '                    <div class="left" @click="changecl()">' +
                    '                        <div class="iconfont icon-left"></div>' +
                    '                    </div>' +
                    '                    <div class="right " style="background-color: #00a2d4;" >' +
                    '                        <div ></div>' +
                    '                    </div>' +
                    '                </div>'+
                '</div>',
        methods:{
            changecl:function()
            {
                this.cl = !this.cl;
            }
        }
    });

	}(window);