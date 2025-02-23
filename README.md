# 高度自动化QQ校园墙Bot

## About
初开发于2024年10月1日，历经四个月的公测与缝缝补补，第一代release横空出世。  
代码稀烂，但注释较齐。  
涉及QQ消息发送的部分遵循[OneBot-v11](https://github.com/botuniverse/onebot-11)协议。  
空间发送部分引用了项目[Qzone-Next](https://github.com/Web-Art-Online/qzone-next)。
背景图采用随机Bingimg。

---

## 初配置

### 1. 相关配置
- **推荐的PHP版本**：≥7.4.33  
- **推荐使用的系统**：Debian系均可  
- **请解除禁用**：`exec`、`shell_exec`函数。

### 2. 管理员配置

#### Linux：
- 需要安装`wkhtmltopdf`：  
  ```bash
  apt-get install wkhtmltopdf
  ```
- 需要自行运行符合OneBotv11 Webhook的QQ客户端（如Napcat）。

#### 数据库：
- 网页端登录`./databases/phpliteadmin.php`  
  - 数据库：`permanence` -> `configurations`表  
  - 字段名：`field`、`value`  
  - 数据：  
    - `superadmin`：管理员QQ号  
    - `supergroup`：管理员QQ群  

- **需要手动更改的php文件**：
  - `api/func.php`内  
    - 152~153行：OneBot Webhook相关配置  
    - 185行：投稿同步发送的QQ群号（年级大群/学校大群）  
    - 201~202行：管理员群/审核群  
    - **需批量替换**：`127.0.0.1:15001` -> 本项目运行的地址，若web端口使用15001则无需更改  

  - `api/dealmsg.php`内  
    - 14行：管理员  
    - 15行：管理员群与审核群（在此处无区别）  
    - 152~153行：同15行，`1234567800`为管理员群  
    - 机器人发送的文案大部分都位于该文件，可手动更改  
    - **需批量替换**：`127.0.0.1:15001` -> 本项目运行的地址，若web端口使用15001则无需更改  

  - `api/autosend.php`内  
    - 5行、10行：同`dealmsg.php`  

  - `api/qzone-next/sample.py`内  
    - 9行：机器人本体QQ号  

### 3. Cron配置
```bash
0 0 * * * ? * php ./api/autosend.php
```

---

## 关于数据库
- 每月的数据会单独建一张表储存。  
- 每年的数据会单独建一个数据库储存。  
- `preptable.php`请勿删除，用于建表建数据库。

---

**GitHub项目地址**：[高度自动化QQ校园墙Bot](https://github.com/airline233/autoschbot)  
**开发者**：@Airline233
**版本**：v1.0.0  
**最后更新**：2025年2月22日  
