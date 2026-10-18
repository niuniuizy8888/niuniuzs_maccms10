# 牛牛助手 · MacCMS10 独立插件版 v2.0.0

给**未集成牛牛助手**的苹果CMS v10 站点使用。不修改任何核心文件，通过 MacCMS 自带的插件机制（应用 → 插件）启用、禁用、卸载。
（已经在用「整合版」CMS 包的站点，后台已自带牛牛助手，**不要**再装本插件。）

## 功能
- 后台一级菜单「牛牛助手」，位置紧跟「首页」：首页、牛牛助手、系统……
- 子页面：概览 / 资源站（一键接入采集接口+播放器+分类绑定+定时采集）/ 资讯采集 / 影视海报 / 批量替换 / 采集任务 / 异步采集 / 助手设置 / 推广
- 保留：远程批量替换（`api.php/auto/replace`）、清空分类、重置会员组权限（危险操作前自动备份到 `application/data/backup/nnzs/`）
- 商店广告位默认开启，每个助手页面都显示（同原插件）
- 对外接口（**不加额外鉴权**，与原插件一致，只校验口令 auth_key）：
  - `api.php/auto/test`、`api.php/auto/replace`、`api.php/auto/update`
  - `api.php/auto/rsync`：异步采集入站，商店 worker 用请求头 `X-Nnzs-Token: <auth_key>` 推送 JSON；GET 带口令返回 pong
  - `api.php/auto/heartbeat`：异步采集心跳（插件版新增，供服务器 crontab 调用）
- 站点标识 siteid 为随机值，**不由后台密码派生**

## 环境要求
- 苹果CMS v10（官方 magicblack/maccms10；新版 view_new 后台与旧版 view 后台都兼容）
- PHP 7.4 及以上（已在 PHP 8.5 实测；需 curl、json、openssl 扩展）
- 服务器能访问商店 `https://api.niuniucms.cc`
- PHP 进程（如 www）对以下目录**可写**：`addons/`、`application/`（启用时要复制文件进去）、`application/extra/`、`application/data/`、`runtime/`、`static/player/`

## 安装
1. 把压缩包里的 `addons/niuniuzs` 整个目录上传到站点根目录的 `addons/` 下（最终路径：`站点根/addons/niuniuzs/info.ini`）。
2. 设置属主/权限，例如：`chown -R www:www addons/niuniuzs`
3. 后台 → 应用 → 插件（应用市场的本地插件列表）找到「牛牛助手」，点「启用」。
4. 刷新后台，顶部菜单出现「牛牛助手」（在「首页」后面），进入后按提示完成「初始化」（生成口令 auth_key，可选导入分类、接入资源站、向商店登记）。
5. 异步采集心跳（可选）：在「异步采集」页可看到心跳地址，加到服务器 crontab，每小时一次，例如：
   `0 * * * * curl -s -H "X-Nnzs-Token: 你的口令" https://你的域名/api.php/auto/heartbeat`

> 启用时插件框架会把 `addons/niuniuzs/application/` 下的文件复制到站点 `application/`：
> `admin/controller/Nnzs.php`、`admin/controller/Auto.php`（旧地址 admin.php/auto/* 跳转）、`api/controller/Auto.php`、
> `common/service/nnzs/*`、`data/nnzs/theme/*`。这些文件官方 MacCMS10 里都没有，框架的冲突检测保证不会覆盖已有文件。

## 权限
- 创始人（ID=1）可使用全部功能。
- 其他管理员：拥有「应用/插件管理」权限即可使用（核心权限树 auth.php 不改动，所以权限树里没有单独的牛牛助手节点）。

## 禁用 / 卸载
- **禁用**：后台 → 应用 → 插件 → 禁用。菜单消失，复制到 `application/` 的文件被删除；配置与异步采集状态保留，重新启用后继续可用。
- **卸载**：点「卸载」。会删除：插件目录 `addons/niuniuzs`（含配置 `data/config.php`、口令）、复制到 `application/` 的全部文件、`application/data/nnzs/`（异步采集状态等）、`runtime/nnzs_*` 锁文件；菜单随之消失。
  会保留：已添加的采集接口、播放器、分类绑定、定时采集任务（都是标准 MacCMS 数据，不依赖本插件），以及 `application/data/backup/nnzs/` 下的操作前备份。
- 卸载后商店端仍保留本站登记信息，如需注销请联系商店。

## 与整合版的差异
- 配置存放在 `addons/niuniuzs/data/config.php`（整合版在 `application/extra/maccms.php` 的 nnzs 段；装插件时若检测到整合版配置会自动读取迁移）。
- 心跳定时任务不写入核心「定时任务」（核心定时任务只认内置类型），改为服务器 crontab 调用 `api.php/auto/heartbeat`。
- 不支持在线覆盖升级：升级时下载新版插件包，覆盖 `addons/niuniuzs/` 后在插件管理里先禁用再启用。
- 后台入口改名（如 admin.php → xxx.php）不影响使用；对外接口固定走 `api.php`（如改过 api 入口名，在商店端登记时注意）。
