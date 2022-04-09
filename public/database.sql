
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for vit_addons
-- ----------------------------
DROP TABLE IF EXISTS `vit_addons`;
CREATE TABLE `vit_addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '应用名称',
  `identifie` varchar(20) DEFAULT NULL COMMENT '应用标识',
  `version` varchar(20) DEFAULT NULL COMMENT '应用版本号',
  `author` varchar(20) DEFAULT NULL COMMENT '开发者名称',
  `logo` varchar(255) DEFAULT NULL,
  `goods_id` varchar(11) DEFAULT NULL COMMENT '萌折市场商品id',
  `xn_sales` int(10) DEFAULT NULL COMMENT '虚拟销量',
  `jianjie` varchar(255) DEFAULT NULL COMMENT '简介',
  `price` mediumtext COMMENT '应用价格',
  `xiangqing` mediumtext COMMENT '富文本详情',
  `create_time` int(11) DEFAULT NULL COMMENT '安装时间',
  `type` int(2) DEFAULT '0' COMMENT '0本地1远程',
  `state` int(2) DEFAULT '0' COMMENT '状态0未上架1上架',
  `is_install` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否安装',
  `yun_version` varchar(255) DEFAULT NULL COMMENT '云端版本号',
  `describe` mediumtext COMMENT '更新说明',
  `up_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `addons_type` varchar(255) DEFAULT NULL COMMENT '应用类型',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_app
-- ----------------------------
DROP TABLE IF EXISTS `vit_app`;
CREATE TABLE `vit_app` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL COMMENT '项目名称',
  `logo` varchar(100) DEFAULT NULL COMMENT '项目封面',
  `addons` varchar(50) DEFAULT NULL COMMENT '应用名称',
  `app_id` int(10) DEFAULT NULL COMMENT '模块id addons id',
  `dq_time` int(11) DEFAULT NULL COMMENT '到期时间',
  `config` mediumtext COMMENT '配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_attachment
-- ----------------------------
DROP TABLE IF EXISTS `vit_attachment`;
CREATE TABLE `vit_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '用户id',
  `session` varchar(50) DEFAULT NULL COMMENT '用户登录session名称',
  `pid` int(50) DEFAULT NULL COMMENT '项目id',
  `storage_id` int(11) DEFAULT NULL COMMENT '储存配置信息id',
  `filename` varchar(255) NOT NULL COMMENT '文件名',
  `fileurl` varchar(255) NOT NULL COMMENT '文件地址',
  `type` varchar(255) NOT NULL COMMENT '类型1图片2音频3视频',
  `storage` int(2) DEFAULT NULL COMMENT '1本地2七牛3腾讯云4阿里云5FTP',
  `createtime` int(11) NOT NULL COMMENT '上传时间',
  `size` int(11) DEFAULT '0' COMMENT '时长',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_auth_map
-- ----------------------------
DROP TABLE IF EXISTS `vit_auth_map`;
CREATE TABLE `vit_auth_map` (
  `admin_id` int(11) NOT NULL COMMENT '用户ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色对应关系表';

-- ----------------------------
-- Table structure for vit_auth_nodes
-- ----------------------------
DROP TABLE IF EXISTS `vit_auth_nodes`;
CREATE TABLE `vit_auth_nodes` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(20) unsigned DEFAULT '0' COMMENT '用户id',
  `rule_id` int(11) DEFAULT NULL COMMENT '角色id',
  `node` varchar(200) DEFAULT '' COMMENT '节点',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_system_auth_auth` (`uid`) USING BTREE,
  KEY `idx_system_auth_node` (`node`(191)) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT COMMENT='系统-授权';

-- ----------------------------
-- Records of vit_auth_nodes
-- ----------------------------
INSERT INTO `vit_auth_nodes` VALUES ('1', '0', '1', 'index/App/edit');
INSERT INTO `vit_auth_nodes` VALUES ('2', '0', '1', 'index/App/index');

-- ----------------------------
-- Table structure for vit_fans
-- ----------------------------
DROP TABLE IF EXISTS `vit_fans`;
CREATE TABLE `vit_fans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT '项目id',
  `type` int(5) DEFAULT NULL COMMENT '1为公众号2为小程序',
  `openid` varchar(50) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `sex` varchar(2) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `headimgurl` varchar(255) DEFAULT NULL,
  `privilege` mediumtext,
  `unionid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='公众号粉丝表';

-- ----------------------------
-- Table structure for vit_menu
-- ----------------------------
DROP TABLE IF EXISTS `vit_menu`;
CREATE TABLE `vit_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL COMMENT '归属应用',
  `title` varchar(20) NOT NULL COMMENT '菜单名称',
  `url` varchar(255) NOT NULL COMMENT '菜单链接',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `pid` int(11) NOT NULL COMMENT '上级节点ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '启用状态',
  `open` tinyint(1) DEFAULT '0' COMMENT '是否新窗口打开 0否 1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统菜单表';

-- ----------------------------
-- Records of vit_menu
-- ----------------------------
INSERT INTO `vit_menu` VALUES ('1', null, 'admin', '首页', 'index/admin/main', 'viticon vit-icon-zhuye', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('2', null, 'admin', '项目管理', 'index/app/index', 'viticon vit-icon-fengge', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('3', null, 'admin', '应用管理', 'index/addons/list', 'viticon vit-icon-diqiu', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('4', null, 'admin', '菜单设置', 'index/menu/index', 'viticon vit-icon-caidan', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('5', null, 'admin', '财务中心', 'index/property/index', 'viticon vit-icon-qian', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('6', null, 'admin', '权限设置', 'index/role/index', 'viticon vit-icon-vip', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('7', null, 'admin', '用户管理', 'index/user/index', 'viticon vit-icon-user', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('8', null, 'admin', '系统管理', '#', 'viticon vit-icon-zhuomian', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('9', null, 'admin', '站点设置', 'index/system/site', 'viticon vit-icon-diqiu', '8', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('10', null, 'admin', '文章管理', 'index/news/index', 'viticon vit-icon-bianji', '8', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('11', null, 'admin', '系统设置', 'index/system/setup', 'viticon vit-icon-shezhi1', '8', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('12', null, 'admin', '系统更新', 'index/system/info', 'viticon vit-icon-fasong', '8', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('13', null, 'admin', '系统日志', 'index/system/log', 'viticon vit-icon-shijian', '8', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('14', '1', 'index', '首页', '/', 'viticon vit-icon-jingyin', '0', '50', '1', null);
INSERT INTO `vit_menu` VALUES ('15', '1', 'index', '文档', '/index/index/news', '', '0', '50', '1', '1');

-- ----------------------------
-- Table structure for vit_news
-- ----------------------------
DROP TABLE IF EXISTS `vit_news`;
CREATE TABLE `vit_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL COMMENT '用户id',
  `tid` int(10) unsigned NOT NULL COMMENT '分类id',
  `title` varchar(100) NOT NULL,
  `cover` varchar(100) NOT NULL COMMENT '封面图片',
  `content` mediumtext NOT NULL,
  `author` varchar(50) NOT NULL,
  `is_display` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示0显示1不显示',
  `createtime` int(10) unsigned NOT NULL,
  `pv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '访问量',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `cateid` (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of vit_news
-- ----------------------------
INSERT INTO `vit_news` VALUES ('1', '1', '1', 'VitPhp管理系统使用教程', '/static/theme/images/minilogo.png', '<p>欢迎使用VitPhp多应用管理系统，接下来我们了解一下系统的基本功能。</p>\r\n\r\n<p>首页：查看系统数据，配置系统公告。</p>\r\n\r\n<p>平台管理：创建项目平台，管理员可以自由分配到期时间和分配用户项目，</p>\r\n\r\n<p>应用管理：可以安装已开发的应用，编辑卸载删除。</p>\r\n\r\n<p>菜单管理，配置后台菜单。</p>\r\n\r\n<p>权限管理：创建用户权限组，自由分配权限。</p>\r\n\r\n<p>用户管理：管理系统用户，可以给用户分配权限和权限组。</p>\r\n\r\n<p>站点设置：配置网站基础信息，每个用户可以设置域名和域名下的参数，当访问该域名时可以显示该域名下的配置。</p>\r\n\r\n<p>文章管理：设置文章，可在主页显示，或者配置公告列表。</p>\r\n\r\n<p>系统设置：创始人权限，配置框架主要信息，可以设置微信登录配置，远程附件配置。</p>\r\n\r\n<p>系统更新：如果系统框架有更新时，可以在后台一键升级版本来体验最新功能。</p>\r\n', 'admin', '0', '1625812970', '0');

-- ----------------------------
-- Table structure for vit_news_type
-- ----------------------------
DROP TABLE IF EXISTS `vit_news_type`;
CREATE TABLE `vit_news_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `title` varchar(30) NOT NULL COMMENT '分类名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of vit_news_type
-- ----------------------------
INSERT INTO `vit_news_type` VALUES ('1', '1', '使用教程');

-- ----------------------------
-- Table structure for vit_pay_log
-- ----------------------------
DROP TABLE IF EXISTS `vit_pay_log`;
CREATE TABLE `vit_pay_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weid` int(11) DEFAULT NULL COMMENT '公众号id',
  `hid` varchar(20) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL COMMENT '用户id',
  `type` varchar(30) DEFAULT NULL COMMENT '支付类型 wechat 微信支付/alipay 支付宝/微信小程序支付 wxapp',
  `pay_type` varchar(50) DEFAULT NULL COMMENT '支付商品分类',
  `openid` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `uniontid` varchar(64) DEFAULT NULL COMMENT '商户订单号',
  `tid` varchar(128) DEFAULT NULL COMMENT '订单号',
  `fee` decimal(10,2) DEFAULT NULL COMMENT '支付金额',
  `status` tinyint(4) unsigned DEFAULT '0' COMMENT '支付状态 0待支付 1已支付',
  `module` varchar(50) DEFAULT NULL COMMENT '支付模块',
  `detail` varchar(3000) DEFAULT NULL COMMENT '订单详情',
  `paytime` int(11) DEFAULT NULL COMMENT '支付时间',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `product_id` varchar(20) DEFAULT NULL COMMENT 'type=NATIVE时，此参数必传。此参数为二维码中包含的商品ID',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_platform
-- ----------------------------
DROP TABLE IF EXISTS `vit_platform`;
CREATE TABLE `vit_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addons` varchar(50) DEFAULT NULL COMMENT '应用标识',
  `pid` int(11) DEFAULT NULL,
  `wechat` mediumtext COMMENT '公众号参数',
  `wxapp` mediumtext COMMENT '微信小程序',
  `workwechat` mediumtext COMMENT '企业微信',
  `ksapp` mediumtext,
  `androidapp` mediumtext,
  `iosapp` mediumtext,
  `dyapp` mediumtext,
  `bdapp` mediumtext,
  `alpayapp` mediumtext,
  `template` mediumtext,
  `web` mediumtext,
  `other` mediumtext COMMENT '其他参数配置',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_property
-- ----------------------------
DROP TABLE IF EXISTS `vit_property`;
CREATE TABLE `vit_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户id',
  `sid` int(11) DEFAULT NULL COMMENT '上级id',
  `ssid` int(11) DEFAULT NULL COMMENT '上上级id',
  `pid` int(11) DEFAULT NULL COMMENT '创建的项目id',
  `gid` int(11) DEFAULT NULL COMMENT '商品id',
  `appid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '为0是新建，有值则是续费',
  `addons` varchar(50) DEFAULT NULL COMMENT '生成订单的应用标识',
  `orderid` varchar(128) DEFAULT NULL COMMENT '站内订单号',
  `uniontid` varchar(128) DEFAULT NULL COMMENT '商户订单号',
  `shopname` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `paytype` int(2) DEFAULT NULL COMMENT '1微信支付2支付宝',
  `create_time` int(11) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL COMMENT '支付时间',
  `describe` varchar(255) DEFAULT NULL COMMENT '订单描述',
  `openid` varchar(100) DEFAULT NULL COMMENT '付款人openid',
  `avatar` varchar(100) DEFAULT NULL COMMENT '头像',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `user_ip` varchar(255) DEFAULT NULL,
  `form_pid` int(11) DEFAULT NULL COMMENT '从哪个项目下单',
  `form_addons` varchar(255) DEFAULT NULL COMMENT '从哪个应用下的单',
  `form_addons_id` int(11) DEFAULT NULL COMMENT '从哪个应用id下的单',
  `paysku` varchar(255) DEFAULT NULL COMMENT '购买的规格信息',
  `fee` varchar(255) DEFAULT NULL COMMENT '支付金额',
  `status` int(2) DEFAULT NULL COMMENT '0未付款1已支付成功',
  `snapshot` mediumtext COMMENT '商品快照',
  `other` mediumtext COMMENT '其他需要备注的东西',
  `code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for vit_qrcode
-- ----------------------------
DROP TABLE IF EXISTS `vit_qrcode`;
CREATE TABLE `vit_qrcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(16) DEFAULT NULL,
  `weid` int(11) DEFAULT NULL,
  `openid` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `nickname` varchar(150) DEFAULT NULL,
  `createtime` varchar(11) DEFAULT NULL,
  `is_login` tinyint(2) DEFAULT '0' COMMENT '是否登录成功  1登录成功 2扫码成功',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2596 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_role
-- ----------------------------
DROP TABLE IF EXISTS `vit_role`;
CREATE TABLE `vit_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(20) NOT NULL COMMENT '名称',
  `desc` varchar(255) DEFAULT NULL COMMENT '介绍',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限集';

-- ----------------------------
-- Records of vit_role
-- ----------------------------
INSERT INTO `vit_role` VALUES ('1', '普通用户', '该用户拥有基础权限', '1', '1626226093');

-- ----------------------------
-- Table structure for vit_routing
-- ----------------------------
DROP TABLE IF EXISTS `vit_routing`;
CREATE TABLE `vit_routing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(5) DEFAULT NULL,
  `pid` int(5) DEFAULT NULL,
  `addons` varchar(50) DEFAULT NULL COMMENT '应用标识',
  `key` mediumtext,
  `name` varchar(100) DEFAULT NULL COMMENT '备注',
  `domain` varchar(255) NOT NULL COMMENT '域名前缀',
  `directory` varchar(255) DEFAULT NULL COMMENT '目录',
  `url` varchar(255) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL COMMENT '类型',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`) USING HASH
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_settings
-- ----------------------------
DROP TABLE IF EXISTS `vit_settings`;
CREATE TABLE `vit_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addons` varchar(255) DEFAULT NULL COMMENT '类型(模块名)',
  `name` varchar(255) DEFAULT NULL,
  `value` mediumtext COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of vit_settings
-- ----------------------------
INSERT INTO `vit_settings` VALUES ('1', 'setup', 'is_reg', '1');
INSERT INTO `vit_settings` VALUES ('2', 'setup', 'interest', '1');
INSERT INTO `vit_settings` VALUES ('3', 'setup', 'imgwidth', '800');
INSERT INTO `vit_settings` VALUES ('4', 'setup', 'imgtype', 'jpg,jpeg,gif,png');
INSERT INTO `vit_settings` VALUES ('5', 'setup', 'mp3type', 'mp3');
INSERT INTO `vit_settings` VALUES ('6', 'setup', 'mp4type', 'mp4');
INSERT INTO `vit_settings` VALUES ('66', 'setup', 'atta_type', '1');

-- ----------------------------
-- Table structure for vit_storage_setup
-- ----------------------------

DROP TABLE IF EXISTS `vit_storage_setup`;
CREATE TABLE `vit_storage_setup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `type` int(2) DEFAULT NULL COMMENT '1本地2七牛3腾讯云4阿里云5FTP',
  `addons` varchar(50) DEFAULT NULL COMMENT '应用标识',
  `note` varchar(255) DEFAULT NULL COMMENT '其他备注',
  `access_key` varchar(100) DEFAULT NULL COMMENT '七牛access_key',
  `secret_key` varchar(100) DEFAULT NULL COMMENT '七牛secret_key',
  `space` varchar(50) DEFAULT NULL COMMENT '七牛空间名称',
  `domain` varchar(100) DEFAULT NULL COMMENT '七牛域名',
  `tx_region` varchar(50) DEFAULT NULL COMMENT '腾讯云访问地址',
  `tx_accesskey` varchar(100) DEFAULT NULL COMMENT '腾讯accesskey',
  `tx_secretkey` varchar(100) DEFAULT NULL COMMENT '腾讯secretkey',
  `tx_space` varchar(50) DEFAULT NULL COMMENT '腾讯储存名称',
  `tx_domain` varchar(100) DEFAULT NULL COMMENT '腾讯云域名',
  `al_region` varchar(50) DEFAULT NULL COMMENT '阿里云访问地址',
  `al_accesskey` varchar(100) DEFAULT NULL COMMENT '阿里云accesskey',
  `al_secretkey` varchar(100) DEFAULT NULL COMMENT '阿里云secretkey',
  `al_intranet` varchar(50) DEFAULT NULL COMMENT '内网上传0关闭1开启',
  `al_space` varchar(50) DEFAULT NULL COMMENT '阿里云空间名称',
  `al_domain` varchar(100) DEFAULT NULL COMMENT '阿里云域名',
  `ftp_ip` varchar(50) DEFAULT NULL COMMENT 'FTP IP地址',
  `ftp_port` varchar(10) DEFAULT NULL COMMENT 'FTP端口',
  `ftp_account` varchar(100) DEFAULT NULL COMMENT 'FTP用户名',
  `ftp_pass` varchar(100) DEFAULT NULL COMMENT 'FTP密码',
  `ftp_path` varchar(100) DEFAULT NULL COMMENT 'FTP储存路径',
  `ftp_domain` varchar(100) DEFAULT NULL COMMENT 'FTP访问域名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_sys_log
-- ----------------------------
DROP TABLE IF EXISTS `vit_sys_log`;
CREATE TABLE `vit_sys_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) DEFAULT NULL,
  `auth` varchar(255) DEFAULT NULL,
  `auth_text` varchar(500) DEFAULT NULL,
  `description` text,
  `ip` varchar(15) DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for vit_system_site
-- ----------------------------
DROP TABLE IF EXISTS `vit_system_site`;
CREATE TABLE `vit_system_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `domain` varchar(50) DEFAULT NULL COMMENT '域名',
  `name` varchar(50) DEFAULT NULL COMMENT '网站名称',
  `title` varchar(255) DEFAULT NULL COMMENT '网站标题',
  `keywords` varchar(255) DEFAULT NULL COMMENT '网站关键字',
  `description` varchar(255) DEFAULT NULL COMMENT '网站描述',
  `favicon` varchar(255) DEFAULT NULL,
  `index_logo` varchar(255) DEFAULT NULL COMMENT '首页logo',
  `admin_logo` varchar(255) DEFAULT NULL COMMENT '后台logo',
  `kf_qq` varchar(100) DEFAULT NULL COMMENT '客服QQ',
  `index_content` mediumtext COMMENT '网站内容',
  `index_hdp` mediumtext COMMENT '幻灯片',
  `icpbeian` varchar(255) DEFAULT NULL,
  `foot_top` varchar(255) DEFAULT NULL COMMENT '底部文字（上）',
  `foot_bottom` varchar(255) DEFAULT NULL COMMENT '底部文字（下）',
  `template` varchar(50) DEFAULT NULL COMMENT '默认模板',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of vit_system_site
-- ----------------------------
INSERT INTO `vit_system_site` VALUES ('1', '1', '', 'vitphp', 'Vitphp多应用管理系统', 'Vitphp多应用管理系统 - 萌折科技', 'Vitphp多应用管理系统 - 萌折科技', null, '', '', '', '', '', '', '', '', '');

-- ----------------------------
-- Table structure for vit_users
-- ----------------------------
DROP TABLE IF EXISTS `vit_users`;
CREATE TABLE `vit_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `openid` varchar(50) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL COMMENT '头像',
  `username` varchar(25) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(20) DEFAULT NULL COMMENT '姓名',
  `login_ip` varchar(20) DEFAULT NULL COMMENT '登录IP',
  `create_time` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `login_num` int(11) DEFAULT '0' COMMENT '登录次数',
  `last_login` int(11) unsigned DEFAULT '0' COMMENT '上次登录时间',
  `is_deleted` int(11) DEFAULT '0' COMMENT '删除位',
  `sid` int(11) DEFAULT NULL COMMENT '上级id',
  `state` int(11) DEFAULT '1' COMMENT '启用状态',
  `pid` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台管理员表';
