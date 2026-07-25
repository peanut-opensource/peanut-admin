-- Peanut Admin 数据库初始化脚本
-- 前缀：pa_
-- 字符集：utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 管理员表
CREATE TABLE IF NOT EXISTS `pa_admin` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '账号',
  `nickname`    VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '昵称',
  `password`    VARCHAR(64)  NOT NULL DEFAULT '' COMMENT '密码（md5(md5(pwd)+salt)）',
  `salt`        VARCHAR(16)  NOT NULL DEFAULT '' COMMENT '密码盐',
  `avatar`      VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
  `root`        TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '超级管理员：0否 1是',
  `disable`     TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '禁用：0否 1是',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `delete_time` INT UNSIGNED NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 系统角色表
CREATE TABLE IF NOT EXISTS `pa_system_role` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '角色名称',
  `desc`        VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
  `sort`        SMALLINT     NOT NULL DEFAULT 0  COMMENT '排序',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统角色表';

-- 管理员-角色关联表
CREATE TABLE IF NOT EXISTS `pa_admin_role` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `role_id`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id`  (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色关联表';

-- 系统菜单表
CREATE TABLE IF NOT EXISTS `pa_system_menu` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid`        INT UNSIGNED NOT NULL DEFAULT 0   COMMENT '父级ID，0为顶级',
  `type`       CHAR(1)      NOT NULL DEFAULT 'C' COMMENT 'M目录 C菜单 A按钮',
  `name`       VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon`       VARCHAR(100) NOT NULL DEFAULT '' COMMENT '图标',
  `sort`       SMALLINT     NOT NULL DEFAULT 0  COMMENT '排序',
  `perms`      VARCHAR(100) NOT NULL DEFAULT '' COMMENT '权限标识（如 auth.admin/lists）',
  `paths`      VARCHAR(200) NOT NULL DEFAULT '' COMMENT '路由路径',
  `component`  VARCHAR(200) NOT NULL DEFAULT '' COMMENT '前端组件',
  `is_cache`   TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '是否缓存',
  `is_show`    TINYINT(1)   NOT NULL DEFAULT 1  COMMENT '是否显示',
  `is_disable` TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '是否禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统菜单表';

-- 角色-菜单关联表
CREATE TABLE IF NOT EXISTS `pa_system_role_menu` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `menu_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '菜单ID',
  PRIMARY KEY (`id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色菜单关联表';

-- 部门表
CREATE TABLE IF NOT EXISTS `pa_dept` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid`         INT UNSIGNED NOT NULL DEFAULT 0  COMMENT '父级ID',
  `name`        VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '部门名称',
  `leader`      VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '负责人',
  `mobile`      VARCHAR(20)  NOT NULL DEFAULT '' COMMENT '联系电话',
  `sort`        SMALLINT     NOT NULL DEFAULT 0  COMMENT '排序',
  `is_disable`  TINYINT(1)   NOT NULL DEFAULT 0  COMMENT '是否禁用',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `pa_operation_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED NOT NULL DEFAULT 0  COMMENT '管理员ID',
  `username`    VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '管理员账号',
  `ip`          VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '操作IP',
  `uri`         VARCHAR(200) NOT NULL DEFAULT '' COMMENT '请求路径',
  `method`      VARCHAR(10)  NOT NULL DEFAULT '' COMMENT '请求方式',
  `params`      TEXT                             COMMENT '请求参数',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS `pa_config` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`        VARCHAR(30)  NOT NULL DEFAULT '' COMMENT '配置类型',
  `name`        VARCHAR(60)  NOT NULL DEFAULT '' COMMENT '配置名称',
  `value`       TEXT                             COMMENT '配置值',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_name` (`type`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 初始化超级管理员（密码：admin123456，salt: abcd1234）
-- 密码 = md5(md5('admin123456') + 'abcd1234')
INSERT IGNORE INTO `pa_admin` (`username`, `nickname`, `password`, `salt`, `root`, `create_time`, `update_time`)
VALUES ('admin', '超级管理员', MD5(CONCAT(MD5('admin123456'), 'abcd1234')), 'abcd1234', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
