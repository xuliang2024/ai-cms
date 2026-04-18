---
name: add-cms-menu
description: 在 DolphinPHP 后台管理系统中创建新的菜单节点和页面。当用户需要添加后台菜单、创建新的管理页面、注册控制器到侧边栏时使用此 skill。
---

# 添加 CMS 后台菜单

## 数据库信息

- **菜单表**: `ai_admin_menu`（在默认库 `openai` 中，前缀 `ai_`）
- **表结构关键字段**:

| 字段 | 类型 | 说明 |
|---|---|---|
| id | int | 自增主键 |
| pid | int | 父菜单 ID（0 表示顶级） |
| module | varchar | 所属模块（如 `video`） |
| title | varchar | 菜单显示名称 |
| icon | varchar | Font Awesome 图标类名 |
| url_type | varchar | 固定为 `module_admin` |
| url_value | varchar | 路由值，格式 `模块/控制器/方法` |
| url_target | varchar | 固定为 `_self` |
| online_hide | tinyint | 1=隐藏（用于 AJAX 等子节点） |
| sort | int | 排序值（越小越靠前） |
| status | tinyint | 1=启用 |

## 操作流程

### 1. 查找父菜单 ID

```bash
ssh root@101.47.73.216 "mysql -u root -p'root123' openai -e \"SELECT id, pid, title, url_value FROM ai_admin_menu WHERE title LIKE '%关键词%' OR module='video'\"" 2>/dev/null
```

常用父菜单 ID：
- `988` — 模型统计（video 模块下的主要功能区）

### 2. 创建控制器文件

路径：`application/{module}/admin/{ControllerName}.php`

```php
<?php
namespace app\{module}\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class YourController extends Admin
{
    public function index()
    {
        // 使用 ZBuilder 构建页面
        return ZBuilder::make('table')
            ->setPageTitle('页面标题')
            ->fetch();
    }
}
```

### 3. 创建模型文件（如需要）

路径：`application/{module}/model/{ModelName}Model.php`

```php
<?php
namespace app\{module}\model;

use think\Model;

class YourModel extends Model
{
    protected $connection = 'translate';  // 或其他数据库连接
    protected $table = 'table_name';      // 完整表名（注意前缀）
}
```

### 4. 插入菜单记录

```bash
ssh root@101.47.73.216 "mysql -u root -p'root123' openai -e \"INSERT INTO ai_admin_menu (pid, module, title, icon, url_type, url_value, url_target, online_hide, create_time, update_time, sort, system_menu, status, params) VALUES ({父菜单ID}, '{模块}', '{菜单名}', 'fa fa-fw fa-{icon}', 'module_admin', '{模块}/{控制器蛇形命名}/{方法}', '_self', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), {排序}, 0, 1, '')\"" 2>/dev/null
```

如果控制器有 AJAX 子方法，需额外添加隐藏子节点：

```bash
ssh root@101.47.73.216 "mysql -u root -p'root123' openai -e \"INSERT INTO ai_admin_menu (pid, module, title, icon, url_type, url_value, url_target, online_hide, create_time, update_time, sort, system_menu, status, params) VALUES ({新菜单ID}, '{模块}', 'AJAX数据', 'fa fa-fw fa-database', 'module_admin', '{模块}/{控制器蛇形命名}/{ajax方法}', '_self', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 0, 1, '')\"" 2>/dev/null
```

### 5. 部署代码

```bash
cd /Users/m007/codes/ai-cms && git add -A && git commit -m "描述" && git push origin master
ssh root@101.47.73.216 "cd /www/wwwroot/ai-cms.fyshark.com && git pull origin master" 2>/dev/null
```

### 6. 验证

- 访问 `https://cms.xskill.ai/admin.php/{模块}/{控制器蛇形命名}/index.html`
- 检查左侧菜单是否显示新菜单项

## 注意事项

- **URL 路由规则**: ThinkPHP 默认使用 PATH_INFO，控制器名自动转蛇形（如 `ApiKeyCallLogMonitor` → `api_key_call_log_monitor`）
- **数据库连接**: 默认库 `openai`（前缀 `ai_`），业务库用 `translate`（前缀 `ts_`），发卡库用 `faka_fyshark_com`（前缀 `hm_`）
- **ZBuilder**: 列表页使用 `ZBuilder::make('table')`，表单页使用 `ZBuilder::make('form')`
- **图表**: ECharts 已集成，通过 `->js("libs/echart/echarts.min")` 引入
- **SQL 保留字**: 使用 `keys`、`order`、`group` 等 MySQL 保留字作别名时必须加反引号
- **ThinkPHP select()**: 返回 Collection 对象，需 `->toArray()` 转数组后才能用 `array_column()` 等原生函数

## 常用图标

| 图标 | 类名 | 适用场景 |
|---|---|---|
| 仪表盘 | `fa-tachometer` | 监控页面 |
| 折线图 | `fa-line-chart` | 统计页面 |
| 柱状图 | `fa-bar-chart` | 报表页面 |
| 用户 | `fa-users` | 用户管理 |
| 设置 | `fa-cog` | 配置页面 |
| 数据库 | `fa-database` | 数据管理 |
| 列表 | `fa-list` | 列表页面 |
