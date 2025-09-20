# 高度自动化QQ校园墙Bot

## About
初开发于2024年10月1日，历经四个月的公测与缝缝补补，第一代release横空出世。  
代码稀烂，但注释较齐。  
涉及QQ消息发送的部分遵循[OneBot-v11](https://github.com/botuniverse/onebot-11)协议。  
~~空间发送部分引用了项目[Qzone-Next](https://github.com/Web-Art-Online/qzone-next)。~~
现使用项目[php-qzone](https://github.com/airline233/php-qzone)
背景图采用随机Bingimg。

---

## 初配置

### 1. 相关配置
- **推荐的PHP版本**：≥7.4.33  
- **推荐使用的系统**：Debian系均可  
- **请解除禁用**：`exec`、`shell_exec`、`popen`函数。
- **注意** 你可能需要在php.ini中手动将“popen”函数从disable列表中删除

### 2. 管理员配置

#### Linux：
- 需要安装`pptr-cli`：  
  ```bash
  npm install -g playwright
  npx playwright install
  ```
- 需要自行运行符合OneBotv11 Webhook的QQ客户端（推荐使用Napcat）。
- 请在sudoer文件中添加：
```
Defaults:www secure_path="/sbin:/bin:/usr/sbin:/usr/bin:你npx的安装目录
www ALL=(ALL) NOPASSWD: /www/server/nodejs/v22.19.0/bin/npx
```
  npx的安装目录可以通过运行`which npx`来获得

#### 数据库：
- 数据库为自动创建，即开即用
- 请配置/api/config.json，样例位于/api/config.sample.json
  - apiaddr：支持OneBot v11标准的webhook地址
  - token：access_token，与onebot通信用
  - superadmin：超级管理员QQ（其实基本没用了）
  - supergroups：管理员群（审核群）
  - database_path：数据库所在路径
  - absaddr：项目部署的绝对地址，位于api目录上一级，需要支持http协议（不一定需要公网）
  - sync_groups：发稿同步的群聊 不推荐太多


### 3. Cron配置
```bash
0 0 * * * ? * php /api/autosend.php
```

---

## 关于数据库
- 每月的数据会单独建一张表储存。  
- 每年的数据会单独建一个数据库储存。  
- `preptable.php`请勿删除，用于自动建表、建数据库。

---

**GitHub项目地址**：[高度自动化QQ校园墙Bot](https://github.com/airline233/autoschbot)  
**开发者**：@Airline233
**版本**：v1.2.0  
**最后更新**：2025年9月20日  
