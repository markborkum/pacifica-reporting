/*
 Navicat Premium Data Transfer

 Source Server         : MyEMSL Development Metadata
 Source Server Type    : PostgreSQL
 Source Server Version : 90409
 Source Host           : 10.0.68.18
 Source Database       : myemsl_metadata
 Source Schema         : website_prefs

 Target Server Type    : SQLite
 Target Server Version : 3000000
 File Encoding         : utf-8

 Date: 09/14/2016 16:43:39 PM
*/

PRAGMA foreign_keys = FALSE;

-- ----------------------------
--  Table structure for reporting_object_group_options
-- ----------------------------
DROP TABLE IF EXISTS "reporting_object_group_options";
CREATE TABLE "reporting_object_group_options" (
	 "group_id" INTEGER NOT NULL,
	 "option_type" TEXT NOT NULL,
	 "option_value" TEXT NOT NULL,
	 "created" TEXT(6,0) NOT NULL,
	 "updated" TEXT(6,0),
	 "deleted" TEXT(6,0),
	CONSTRAINT "reporting_object_group_options_pkey" PRIMARY KEY("group_id","option_type")
);

-- ----------------------------
--  Records of reporting_object_group_options
-- ----------------------------
BEGIN;
INSERT INTO "reporting_object_group_options" VALUES ('31', 'time_range', 'custom', '2016-08-25 14:59:00.717474-07', '2016-08-25 14:59:00.717474-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('13', 'time_basis', 'submit_time', '2016-07-18 16:21:48.506302-07', '2016-07-20 16:52:59.19083-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('15', 'time_range', 'custom', '2016-07-25 09:15:01.333831-07', '2016-07-25 09:15:01.333831-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('8', 'start_time', '2016-07-07', '2016-07-08 12:25:22.788858-07', '2016-09-07 10:56:08.628118-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('8', 'end_time', '2016-07-12', '2016-07-08 12:25:22.823001-07', '2016-09-07 10:56:08.640699-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('13', 'time_range', 'custom', '2016-07-11 14:16:36.413806-07', '2016-07-20 16:53:05.959374-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('9', 'time_basis', 'submit_time', '2016-07-19 11:20:58.94601-07', '2016-07-19 11:20:58.94601-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('12', 'time_range', 'custom', '2016-07-11 14:14:39.143076-07', '2016-07-18 16:42:50.830589-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('31', 'start_time', '2016-02-25', '2016-08-25 14:59:02.982645-07', '2016-08-25 15:07:31.828702-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('25', 'time_range', '3 months', '2016-07-29 16:12:14.371531-07', '2016-07-29 16:20:09.581293-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('25', 'start_time', '2015-05-27', '2016-07-29 16:20:09.596991-07', '2016-07-29 16:20:09.596991-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('25', 'end_time', '2015-08-27', '2016-07-29 16:20:09.608276-07', '2016-07-29 16:20:09.608276-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('12', 'time_basis', 'submit_time', '2016-07-18 16:42:25.242725-07', '2016-07-19 14:53:22.042953-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('9', 'time_range', 'custom', '2016-07-11 12:00:05.86049-07', '2016-07-19 11:21:08.448838-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('31', 'end_time', '2016-08-22', '2016-08-25 14:59:03.022078-07', '2016-08-25 15:07:31.841638-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('12', 'start_time', '2016-07-07', '2016-07-11 14:14:03.722827-07', '2016-08-25 15:08:33.254833-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('12', 'end_time', '2016-08-23', '2016-07-11 14:14:03.740073-07', '2016-08-25 15:08:33.275033-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('7', 'time_range', '3 months', '2016-07-07 15:37:00.806478-07', '2016-07-07 15:37:00.806478-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('32', 'start_time', '2016-08-18', '2016-08-25 15:24:59.875761-07', '2016-08-25 15:24:59.875761-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('13', 'start_time', '2016-07-07 00:00:00', '2016-07-11 14:15:52.881402-07', '2016-08-25 15:25:52.671844-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('13', 'end_time', '2016-08-25', '2016-07-11 14:15:52.895109-07', '2016-08-25 15:25:59.388161-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('32', 'end_time', '2016-08-22', '2016-08-25 15:24:59.888029-07', '2016-08-25 15:31:31.33723-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('32', 'time_range', 'custom', '2016-08-25 15:31:31.351101-07', '2016-08-25 15:31:31.351101-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('29', 'start_time', '2016-08-19', '2016-08-26 13:15:32.777387-07', '2016-08-26 13:15:32.777387-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('29', 'end_time', '2016-08-26', '2016-08-26 13:15:32.791642-07', '2016-08-26 13:15:32.791642-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('15', 'start_time', '2015-09-06', '2016-07-25 09:14:38.996165-07', '2016-09-06 12:04:10.827218-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('15', 'end_time', '2016-08-31', '2016-07-25 09:14:39.010117-07', '2016-09-06 12:04:10.840224-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('9', 'start_time', '2016-07-07', '2016-07-11 11:59:44.242659-07', '2016-09-02 16:51:37.179509-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('9', 'end_time', '2016-08-25', '2016-07-11 11:59:44.256121-07', '2016-09-02 16:51:37.402688-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('8', 'time_range', 'custom', '2016-07-08 17:14:05.34608-07', '2016-07-19 11:18:01.744608-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('27', 'time_basis', 'submit_time', '2016-08-25 14:26:20.031569-07', '2016-08-25 14:26:20.031569-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('14', 'time_range', 'custom', '2016-07-19 11:01:23.090489-07', '2016-07-19 11:18:24.29194-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('7', 'end_time', '2015-08-27', '2016-07-11 11:59:39.377003-07', '2016-07-29 15:55:52.158872-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('7', 'start_time', '2015-07-27', '2016-07-11 11:59:39.362792-07', '2016-07-29 15:55:52.176429-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('21', 'start_time', '2016-08-16', '2016-08-01 11:01:48.401706-07', '2016-08-23 14:48:43.879174-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('21', 'time_range', '3 months', '2016-08-23 14:48:43.930278-07', '2016-08-23 14:48:43.930278-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('21', 'end_time', '2016-08-22', '2016-08-01 11:01:48.419782-07', '2016-08-25 14:20:11.400847-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('27', 'time_range', 'custom', '2016-08-25 14:25:20.924533-07', '2016-08-25 14:27:24.240931-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('14', 'time_basis', 'submit_time', '2016-07-19 11:01:38.591914-07', '2016-09-06 11:18:40.007887-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('27', 'start_time', '2016-07-07 00:00:00', '2016-08-25 14:21:30.25449-07', '2016-08-25 14:29:12.59698-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('27', 'end_time', '2016-08-25', '2016-08-25 14:21:30.253231-07', '2016-08-25 14:30:23.421322-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('14', 'start_time', '2016-07-07', '2016-07-19 11:00:55.014451-07', '2016-09-06 11:59:36.265266-07', null);
INSERT INTO "reporting_object_group_options" VALUES ('14', 'end_time', '2016-09-06', '2016-07-19 11:00:55.030249-07', '2016-09-06 11:59:36.278246-07', null);
COMMIT;

-- ----------------------------
--  Table structure for reporting_object_group_option_defaults
-- ----------------------------
DROP TABLE IF EXISTS "reporting_object_group_option_defaults";
CREATE TABLE "reporting_object_group_option_defaults" (
	 "option_type" TEXT NOT NULL,
	 "option_default" TEXT NOT NULL,
	 "created" TEXT(6,0) NOT NULL,
	 "updated" TEXT(6,0) NOT NULL,
	 "deleted" TEXT(6,0),
	CONSTRAINT "reporting_object_group_option_defaults_pkey" PRIMARY KEY("option_type","option_default")
);

-- ----------------------------
--  Records of reporting_object_group_option_defaults
-- ----------------------------
BEGIN;
INSERT INTO "reporting_object_group_option_defaults" VALUES ('time_range', '3-months', '2016-07-07 15:36:31.812153-07', '2016-07-07 15:36:31.812153-07', null);
INSERT INTO "reporting_object_group_option_defaults" VALUES ('end_time', 0, '2016-07-07 15:36:39.348412-07', '2016-07-07 15:36:39.348412-07', null);
INSERT INTO "reporting_object_group_option_defaults" VALUES ('start_time', 0, '2016-07-07 15:36:43.004-07', '2016-07-07 15:36:43.004-07', null);
INSERT INTO "reporting_object_group_option_defaults" VALUES ('time_basis', 'modified_time', '2016-07-07 15:36:50.876366-07', '2016-07-07 15:36:50.876366-07', null);
COMMIT;

-- ----------------------------
--  Table structure for reporting_selection_prefs
-- ----------------------------
DROP TABLE IF EXISTS "reporting_selection_prefs";
CREATE TABLE "reporting_selection_prefs" (
	 "eus_person_id" INTEGER NOT NULL,
	 "item_type" TEXT NOT NULL,
	 "item_id" TEXT NOT NULL,
	 "group_id" INTEGER NOT NULL,
	 "created" TEXT(6,0) NOT NULL,
	 "updated" TEXT(6,0) NOT NULL,
	 "deleted" TEXT(6,0),
	CONSTRAINT "reporting_selection_prefs_new_pkey" PRIMARY KEY("eus_person_id","item_type","item_id","group_id")
);

-- ----------------------------
--  Records of reporting_selection_prefs
-- ----------------------------
BEGIN;
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'instrument', 34075, '8', '2016-07-08 17:13:28.928913-07', '2016-07-08 17:13:28.928913-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'instrument', 1003, '8', '2016-07-08 17:13:54.796589-07', '2016-07-08 17:13:54.796589-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'proposal', 45796, '9', '2016-07-11 12:00:02.776076-07', '2016-07-11 12:00:02.776076-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34075, '12', '2016-07-11 14:14:34.734705-07', '2016-07-11 14:14:34.734705-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'user', 50724, '13', '2016-07-11 14:16:05.548126-07', '2016-07-11 14:16:05.548126-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'user', 43751, '13', '2016-07-11 14:16:26.653189-07', '2016-07-11 14:16:26.653189-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'proposal', 45796, '14', '2016-07-19 11:01:11.83894-07', '2016-07-19 11:01:11.83894-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'proposal', 45796, '15', '2016-07-25 09:14:57.375211-07', '2016-07-25 09:14:57.375211-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'user', 43751, '7', '2016-07-29 15:55:39.902735-07', '2016-07-29 15:55:39.902735-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'user', 41853, '7', '2016-07-29 15:55:49.155265-07', '2016-07-29 15:55:49.155265-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'user', 43751, '25', '2016-07-29 16:12:06.375352-07', '2016-07-29 16:12:06.375352-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'user', 41853, '25', '2016-07-29 16:12:24.013619-07', '2016-07-29 16:12:24.013619-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('43751', 'proposal', 39713, '9', '2016-08-03 17:10:15.51238-07', '2016-08-03 17:10:15.51238-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('34002', 'user', 34002, '21', '2016-08-23 14:48:35.854386-07', '2016-08-23 14:48:35.854386-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('34002', 'proposal', 45796, '27', '2016-08-25 14:20:34.038358-07', '2016-08-25 14:20:34.038358-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34032, '31', '2016-08-25 14:58:52.149394-07', '2016-08-25 14:58:52.149394-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34179, '31', '2016-08-25 14:58:53.533213-07', '2016-08-25 14:58:53.533213-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34180, '31', '2016-08-25 14:58:54.850641-07', '2016-08-25 14:58:54.850641-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34178, '31', '2016-08-25 14:58:55.744301-07', '2016-08-25 14:58:55.744301-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34219, '31', '2016-08-25 14:58:56.820016-07', '2016-08-25 14:58:56.820016-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34105, '31', '2016-08-25 14:58:58.085812-07', '2016-08-25 14:58:58.085812-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'instrument', 34075, '31', '2016-08-25 14:59:40.172671-07', '2016-08-25 14:59:40.172671-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'user', 46930, '13', '2016-08-25 15:24:54.047176-07', '2016-08-25 15:24:54.047176-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'user', 34002, '32', '2016-08-25 15:31:26.499693-07', '2016-08-25 15:31:26.499693-07', null);
INSERT INTO "reporting_selection_prefs" VALUES ('50724', 'user', 34002, '13', '2016-08-25 15:32:07.087009-07', '2016-08-25 15:32:07.087009-07', null);
COMMIT;

-- ----------------------------
--  Table structure for reporting_object_groups
-- ----------------------------
DROP TABLE IF EXISTS "reporting_object_groups";
CREATE TABLE "reporting_object_groups" (
	 "group_id" INTEGER NOT NULL,
	 "person_id" INTEGER NOT NULL,
	 "group_name" TEXT NOT NULL,
	 "group_type" TEXT NOT NULL,
	 "ordering" INTEGER NOT NULL,
	 "created" TEXT(6,0) NOT NULL,
	 "updated" TEXT(6,0),
	 "deleted" TEXT(6,0),
	CONSTRAINT "reporting_object_groups_pkey" PRIMARY KEY("group_id")
);

-- ----------------------------
--  Records of reporting_object_groups
-- ----------------------------
BEGIN;
INSERT INTO "reporting_object_groups" VALUES ('1', '51342', 'ops_ian_report_test', 'instrument', '0', '2016-07-06 16:22:51.897215-07', '2016-07-06 16:22:51.897215-07', null);
INSERT INTO "reporting_object_groups" VALUES ('2', '51342', 'ops_ian_report_test [2016-07-06 16:23:01]', 'instrument', '0', '2016-07-06 16:23:01.442521-07', '2016-07-06 16:23:01.442521-07', null);
INSERT INTO "reporting_object_groups" VALUES ('7', '43751', 'MyEMSL Test', 'user', '0', '2016-07-07 15:31:13.77581-07', '2016-07-07 15:31:13.77581-07', null);
INSERT INTO "reporting_object_groups" VALUES ('8', '43751', 'MyEMSL Test Group', 'instrument', '0', '2016-07-07 17:19:13.856027-07', '2016-07-07 17:19:13.856027-07', null);
INSERT INTO "reporting_object_groups" VALUES ('9', '43751', 'MyEMSL Test Group [2016-07-07 17:43:12]', 'proposal', '0', '2016-07-07 17:43:12.560093-07', '2016-07-07 17:43:12.560093-07', null);
INSERT INTO "reporting_object_groups" VALUES ('12', '50724', 'Hood', 'instrument', '0', '2016-07-11 14:14:00.52522-07', '2016-07-11 14:14:00.52522-07', null);
INSERT INTO "reporting_object_groups" VALUES ('13', '50724', 'MyEMSL', 'user', '0', '2016-07-11 14:15:50.189756-07', '2016-07-11 14:15:50.189756-07', null);
INSERT INTO "reporting_object_groups" VALUES ('14', '50724', ' MyEMSL', 'proposal', '0', '2016-07-19 11:00:42.260395-07', '2016-07-19 11:00:42.260395-07', null);
INSERT INTO "reporting_object_groups" VALUES ('15', '50724', 'MyEMSL - Mod Time', 'proposal', '0', '2016-07-25 09:14:23.989229-07', '2016-07-25 09:14:23.989229-07', null);
INSERT INTO "reporting_object_groups" VALUES ('21', '34002', 'Me', 'user', '0', '2016-07-29 15:58:38.639841-07', '2016-07-29 15:58:38.639841-07', null);
INSERT INTO "reporting_object_groups" VALUES ('25', '43751', 'MyEMSL New', 'user', '0', '2016-07-29 16:11:51.550075-07', '2016-07-29 16:11:51.550075-07', null);
INSERT INTO "reporting_object_groups" VALUES ('27', '34002', 'MyEMSL 2.0', 'proposal', '0', '2016-08-25 14:20:20.450047-07', '2016-08-25 14:20:20.450047-07', null);
INSERT INTO "reporting_object_groups" VALUES ('28', '34002', 'Test', 'proposal', '0', '2016-08-25 14:39:10.093257-07', '2016-08-25 14:39:10.093257-07', null);
INSERT INTO "reporting_object_groups" VALUES ('29', '50724', 'Test', 'proposal', '0', '2016-08-25 14:50:17.261091-07', '2016-08-25 14:50:17.261091-07', null);
INSERT INTO "reporting_object_groups" VALUES ('30', '34002', 'Computing', 'instrument', '0', '2016-08-25 14:53:23.439301-07', '2016-08-25 14:53:23.439301-07', null);
INSERT INTO "reporting_object_groups" VALUES ('31', '50724', 'Test ', 'instrument', '0', '2016-08-25 14:57:46.576955-07', '2016-08-25 14:57:46.576955-07', null);
INSERT INTO "reporting_object_groups" VALUES ('32', '50724', 'Test User', 'user', '0', '2016-08-25 15:19:17.156105-07', '2016-08-25 15:19:17.156105-07', null);
COMMIT;

-- ----------------------------
--  Table structure for reporting_authz_group
-- ----------------------------
DROP TABLE IF EXISTS "reporting_authz_group";
CREATE TABLE "reporting_authz_group" (
	 "auth_group_id" INTEGER NOT NULL,
	 "auth_group_name" TEXT NOT NULL,
	 "permission_level" INTEGER NOT NULL,
	 "description" TEXT,
	CONSTRAINT "reporting_authz_group_pkey" PRIMARY KEY("auth_group_id")
);

-- ----------------------------
--  Records of reporting_authz_group
-- ----------------------------
BEGIN;
INSERT INTO "reporting_authz_group" VALUES ('1', 'emsl_management', '999', null);
INSERT INTO "reporting_authz_group" VALUES ('2', 'emsl_capability_leads', '700', null);
COMMIT;

-- ----------------------------
--  Table structure for reporting_authz_group_members
-- ----------------------------
DROP TABLE IF EXISTS "reporting_authz_group_members";
CREATE TABLE "reporting_authz_group_members" (
	 "group_member_id" INTEGER NOT NULL,
	 "person_id" INTEGER NOT NULL,
	 "auth_group_id" INTEGER NOT NULL,
	 "created" TEXT(6,0) NOT NULL,
	 "updated" TEXT(6,0) NOT NULL,
	 "deleted" TEXT(6,0),
	CONSTRAINT "reporting_authz_group_members_pkey" PRIMARY KEY("group_member_id")
);

PRAGMA foreign_keys = true;
