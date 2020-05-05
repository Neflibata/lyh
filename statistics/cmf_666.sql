/*
Navicat MySQL Data Transfer

Source Server         : 192.168.0.2
Source Server Version : 50714
Source Host           : 192.168.0.2:3306
Source Database       : lhyd

Target Server Type    : MYSQL
Target Server Version : 50714
File Encoding         : 65001

Date: 2020-03-11 16:48:23
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for cmf_statistics_face_emphasis
-- ----------------------------
DROP TABLE IF EXISTS `cmf_statistics_face_emphasis`;
CREATE TABLE `cmf_statistics_face_emphasis` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ageGroup` char(50) DEFAULT NULL COMMENT '年龄段',
  `gender` char(50) DEFAULT NULL COMMENT '性别',
  `glass` char(50) DEFAULT NULL COMMENT '是否戴眼镜',
  `bkgUrl` varchar(255) DEFAULT NULL COMMENT '背景图片URL',
  `faceUrl` varchar(255) DEFAULT NULL COMMENT '人脸图片URL',
  `faceTime` char(32) DEFAULT NULL COMMENT '抓拍图片的时间',
  `faceMatch` char(50) DEFAULT NULL COMMENT '识别到的目标信息',
  `faceGroupCode` char(64) DEFAULT NULL COMMENT '目标所属的人脸分组的唯一标识',
  `faceGroupName` char(64) DEFAULT NULL COMMENT '目标所属的人脸分组的名称',
  `faceInfoCode` char(64) DEFAULT NULL COMMENT '目标对应的人脸的唯一标识',
  `faceInfoName` char(64) DEFAULT NULL COMMENT '目标对应的人脸的名称',
  `faceInfoSex` char(64) DEFAULT NULL COMMENT '目标对应的人脸的性别',
  `certificate` char(32) DEFAULT NULL COMMENT '目标对应的人脸的证件号码',
  `similarity` char(32) DEFAULT NULL COMMENT '目标人脸和抓拍人脸的相似度',
  `facePicUrl` varchar(255) DEFAULT NULL COMMENT '目标人脸的图片',
  `srcEventId` char(64) DEFAULT NULL COMMENT '源事件的唯一标识',
  `resourceType` char(32) DEFAULT NULL COMMENT '资源类型',
  `indexCode` char(64) DEFAULT NULL COMMENT '资源的唯一标识',
  `cn` char(32) DEFAULT NULL COMMENT '资源的名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of cmf_statistics_face_emphasis
-- ----------------------------
INSERT INTO `cmf_statistics_face_emphasis` VALUES ('1', '', 'sdf165416s', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null);

-- ----------------------------
-- Table structure for cmf_statistics_face_group
-- ----------------------------
DROP TABLE IF EXISTS `cmf_statistics_face_group`;
CREATE TABLE `cmf_statistics_face_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `indexCode` char(50) DEFAULT NULL COMMENT '人脸分组的唯一标识',
  `name` char(50) DEFAULT NULL COMMENT '人脸分组的名称',
  `description` varchar(255) DEFAULT NULL COMMENT '人脸分组的描述',
  `create_time` int(11) unsigned DEFAULT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of cmf_statistics_face_group
-- ----------------------------
INSERT INTO `cmf_statistics_face_group` VALUES ('1', 'bf04c407-e727-4860-940c-3593e4e1b446', '正常', '正常人员', null, null);

-- ----------------------------
-- Table structure for cmf_statistics_face_photo
-- ----------------------------
DROP TABLE IF EXISTS `cmf_statistics_face_photo`;
CREATE TABLE `cmf_statistics_face_photo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET latin1 DEFAULT NULL COMMENT '抓拍到的人脸图片的URL',
  `agegroup` varchar(255) CHARACTER SET latin1 DEFAULT NULL COMMENT '年龄段',
  `height` int(11) DEFAULT NULL COMMENT '人脸高度',
  `width` int(11) DEFAULT NULL COMMENT '人脸宽度',
  `x` char(40) CHARACTER SET latin1 DEFAULT NULL,
  `y` char(40) CHARACTER SET latin1 DEFAULT NULL,
  `bkgUrl` varchar(255) DEFAULT NULL COMMENT '抓拍图片的完整原图',
  `cameraIndexCode` varchar(255) DEFAULT NULL COMMENT '抓拍这张图片的监控点的唯一标识',
  `deviceIndexCode` varchar(255) DEFAULT NULL COMMENT '抓拍这张图片的监控点所属的设备的唯一标识',
  `faceTime` char(40) DEFAULT NULL COMMENT '抓拍这张图片时的时间',
  `type` tinyint(1) unsigned DEFAULT NULL COMMENT '过滤类型	0-全部类型,1-人脸抓拍图片过滤,2-黑名单库,3-白名单库',
  `totalScore` char(20) DEFAULT NULL COMMENT '人脸评分人脸总评分：综合所有评分项得到人脸总评分,数值越大,人脸质量越高0-1',
  `channelID` char(40) DEFAULT NULL COMMENT '抓拍这张图片的监控点的通道号',
  `dataType` varchar(255) DEFAULT NULL COMMENT '人脸比对的事件类别为faceMatch, 最大长度：128',
  `ipAddress` varchar(255) DEFAULT NULL COMMENT '事件来源的地址，人脸抓拍的事件来源为抓拍机的地址。',
  `portNo` varchar(255) DEFAULT NULL COMMENT '事件来源的端口',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of cmf_statistics_face_photo
-- ----------------------------

-- ----------------------------
-- Table structure for cmf_statistics_face_stranger
-- ----------------------------
DROP TABLE IF EXISTS `cmf_statistics_face_stranger`;
CREATE TABLE `cmf_statistics_face_stranger` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ageGroup` char(32) DEFAULT NULL COMMENT '年龄段',
  `gender` char(32) DEFAULT NULL COMMENT '性别',
  `glass` char(32) DEFAULT NULL COMMENT '是否戴眼镜',
  `bkgUrl` varchar(255) DEFAULT NULL COMMENT '背景图片URL',
  `faceUrl` varchar(255) DEFAULT NULL COMMENT '人脸图片URL',
  `faceTime` char(32) DEFAULT NULL COMMENT '抓拍图片的时间',
  `srcEventId` char(64) DEFAULT NULL COMMENT '源事件的唯一标识',
  `resourceType` char(32) DEFAULT NULL COMMENT '资源类型',
  `indexCode` char(64) DEFAULT NULL COMMENT '资源的唯一标识',
  `cn` char(32) DEFAULT NULL COMMENT '资源的名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of cmf_statistics_face_stranger
-- ----------------------------
