define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'project/index',
                    add_url: 'project/add',
                    edit_url: 'project/edit',
                    del_url: 'project/del',
                    multi_url: 'project/multi',
                    multi_url: 'project/site',
                    table: 'projcet',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'num', title: __('Num')},
                        {field: 'name', title: __('Name'), formatter: Controller.api.formatter.jumpurl},
                        {field: 'user.username', title: __('User.username')},
                        {field: 'startime', title: __('Startime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'address', title: __('Address')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"-1":__('Status -1')}, formatter: Table.api.formatter.status},
                        {field: 'days', title: __('Days')},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'pay_status', title: __('Pay_status'), searchList: {"0":__('Pay_status 0'),"1":__('Pay_status 1')}, formatter: Table.api.formatter.status},
                        {field: 'admin.username', title: __('Admin.username')},
                        {field: 'operate', title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	 field: 'operate',
                             width: "120px",
                             title: __('Operate'),
                             buttons: [
 					                        {
 					                            name: __('Detail'),
 					                            title: __('Detail'),
 					                            text: __('Detail'),
 					                            classname: 'btn btn-xs btn-warning btn-addtabs',
 					                            //icon: 'fa fa-folder-o',
 					                            url: 'order/index'
 					                        }
 				                        ],
                        	formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
            	jumpurl: function (value, row, index) {
                    //这里手动构造URL
                    url = "order/index?ids=" + row.id;

                    //方式一,直接返回class带有addtabsit的链接,这可以方便自定义显示内容
                    return '<a href="' + url + '"title="' + __("Search %s", value) + '">' + value + '</a>';

                    //方式二,直接调用Table.api.formatter.addtabs
                    return Table.api.formatter.addtabs(value, row, index, url);
                }
            }
        }

    };
    return Controller;
});