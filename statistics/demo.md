```
|-- public                                              `WEB 部署目录`
|   |-- plugins                                         `插件目录`
|       |-- demo                                        `插件名称`
|           |-- config                                  `插件配置目录`
|               |-- config.json                         `插件配置文件`
|           |-- controller                              `插件控制器目录`
|               |-- AdminIndexController.php            `插件后台管理控制器`
|               |-- ApiIndexController.php              `插件API控制器`
|               |-- DemoConfigController.php            `插件后台管理控制器`
|               |-- DemoController.php                  `插件后台管理控制器`
|           |-- data                                    `插件数据目录`
|               |-- cmf_demo.sql                        `插件所需安装数据表文件`
|               |-- config.php                          `插件所需对应数据表字段文件`
|           |-- lang                                    `插件语言包目录`
|               |-- en-us.php                           `插件语言英文`
|               |-- zh-cn.php                           `插件语言中文`
|           |-- model                                   `插件模型目录`
|               |-- PluginApiIndexModel.php             `插件api模型管理`
|           |-- view                                    `插件视图目录`
|               |-- demo                                `插件视图-模块演示`
|                   |-- addDemoPage.html
|                   |-- detailDemoPage.html
|                   |-- editDemoPage.html
|                   |-- index.html
|               |-- demoConfig                          `插件视图-基本配置`
|                   |-- index.html
|               |-- public                              `插件视图-公共目录`
|                   |-- head.html
|                   |-- scripts.html
|           |-- DemoPlugin.php                          `插件类主文件`

```