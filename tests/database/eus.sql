/*
 Navicat Premium Data Transfer

 Source Server         : MyEMSL Development Metadata
 Source Server Type    : PostgreSQL
 Source Server Version : 90409
 Source Host           : 10.0.68.18
 Source Database       : myemsl_metadata
 Source Schema         : eus

 Target Server Type    : PostgreSQL
 Target Server Version : 90409
 File Encoding         : utf-8

 Date: 09/23/2016 11:07:56 AM
*/

-- ----------------------------
--  Table structure for proposal_instruments
-- ----------------------------
DROP TABLE IF EXISTS "proposal_instruments";
CREATE TABLE "proposal_instruments" (
	"proposal_instrument_id" int4 NOT NULL,
	"instrument_id" int4 NOT NULL,
	"proposal_id" varchar(10) NOT NULL COLLATE "default",
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now()
)
WITH (OIDS=FALSE);
ALTER TABLE "proposal_instruments" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for proposal_members
-- ----------------------------
DROP TABLE IF EXISTS "proposal_members";
CREATE TABLE "proposal_members" (
	"proposal_member_id" int4 NOT NULL,
	"proposal_id" varchar(10) NOT NULL DEFAULT 0 COLLATE "default",
	"person_id" int4 NOT NULL,
	"active" char(1) NOT NULL DEFAULT 'Y'::bpchar COLLATE "default",
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now()
)
WITH (OIDS=FALSE);
ALTER TABLE "proposal_members" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for proposals
-- ----------------------------
DROP TABLE IF EXISTS "proposals";
CREATE TABLE "proposals" (
	"proposal_id" varchar(10) NOT NULL COLLATE "default",
	"title" varchar COLLATE "default",
	"group_id" int4,
	"accepted_date" date,
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now(),
	"actual_end_date" date,
	"actual_start_date" date,
	"closed_date" date
)
WITH (OIDS=FALSE);
ALTER TABLE "proposals" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for instruments
-- ----------------------------
DROP TABLE IF EXISTS "instruments";
CREATE TABLE "instruments" (
	"instrument_id" int4 NOT NULL,
	"instrument_name" varchar NOT NULL COLLATE "default",
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now(),
	"name_short" varchar(100) COLLATE "default",
	"eus_display_name" varchar COLLATE "default",
	"active_sw" char(1) DEFAULT 'Y'::bpchar COLLATE "default"
)
WITH (OIDS=FALSE);
ALTER TABLE "instruments" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for emsl_staff_inst
-- ----------------------------
DROP TABLE IF EXISTS "emsl_staff_inst";
CREATE TABLE "emsl_staff_inst" (
	"instrument_id" int4 NOT NULL,
	"person_id" int4 NOT NULL
)
WITH (OIDS=FALSE);
ALTER TABLE "emsl_staff_inst" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for proposal_pocs
-- ----------------------------
DROP TABLE IF EXISTS "proposal_pocs";
CREATE TABLE "proposal_pocs" (
	"proposal_poc_id" int4 NOT NULL,
	"poc_employee_id" varchar(10) NOT NULL COLLATE "default",
	"proposal_id" varchar(10) NOT NULL COLLATE "default",
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now()
)
WITH (OIDS=FALSE);
ALTER TABLE "proposal_pocs" OWNER TO "metadata_admins";

-- ----------------------------
--  Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
	"person_id" int4 NOT NULL,
	"network_id" varchar(96) COLLATE "default",
	"first_name" varchar(64) COLLATE "default",
	"last_name" varchar(64) COLLATE "default",
	"email_address" varchar(64) COLLATE "default",
	"last_change_date" timestamp(6) WITH TIME ZONE NOT NULL DEFAULT now(),
	"emsl_employee" varchar(1) COLLATE "default"
)
WITH (OIDS=FALSE);
ALTER TABLE "users" OWNER TO "metadata_admins";

-- ----------------------------
--  View structure for v_instrument_groupings
-- ----------------------------
DROP VIEW IF EXISTS "v_instrument_groupings";
CREATE VIEW "v_instrument_groupings" AS  SELECT
        CASE
            WHEN (strpos((i.instrument_name)::text, ':'::text) > 0) THEN rtrim(substr((i.instrument_name)::text, 1, strpos((i.instrument_name)::text, ':'::text)), ':'::text)
            ELSE 'Miscellaneous'::text
        END AS instrument_grouping,
        CASE
            WHEN (strpos((i.instrument_name)::text, ':'::text) > 0) THEN (ltrim(substr((i.instrument_name)::text, strpos((i.instrument_name)::text, ':'::text)), ' :'::text))::character varying
            ELSE i.instrument_name
        END AS instrument_name,
    i.instrument_id,
        CASE
            WHEN (strpos((i.name_short)::text, ':'::text) > 0) THEN (ltrim(substr((i.name_short)::text, strpos((i.name_short)::text, ':'::text)), ' :'::text))::character varying
            ELSE i.name_short
        END AS name_short
   FROM instruments i
  WHERE (i.active_sw = 'Y'::bpchar)
  ORDER BY
        CASE
            WHEN (strpos((i.instrument_name)::text, ':'::text) > 0) THEN rtrim(substr((i.instrument_name)::text, 1, strpos((i.instrument_name)::text, ':'::text)), ':'::text)
            ELSE 'Miscellaneous'::text
        END,
        CASE
            WHEN (strpos((i.instrument_name)::text, ':'::text) > 0) THEN (ltrim(substr((i.instrument_name)::text, strpos((i.instrument_name)::text, ':'::text)), ' :'::text))::character varying
            ELSE i.instrument_name
        END;

-- ----------------------------
--  View structure for v_instrument_search
-- ----------------------------
DROP VIEW IF EXISTS "v_instrument_search";
CREATE VIEW "v_instrument_search" AS  SELECT ig.instrument_id AS id,
    ((((('['::text || COALESCE(ig.instrument_grouping, 'None'::text)) || ' / ID:'::text) || ig.instrument_id) || '] '::text) || (ig.instrument_name)::text) AS display_name,
    lower(((((((COALESCE(ig.instrument_grouping, 'None'::text) || '|'::text) || ig.instrument_id) || '|'::text) || (ig.instrument_name)::text) || '|'::text) || (ig.name_short)::text)) AS search_field,
    ((COALESCE(ig.instrument_grouping, 'None'::text) || '|'::text) || (ig.instrument_name)::text) AS order_field,
    COALESCE(ig.instrument_grouping, 'None'::text) AS category,
    COALESCE(ig.name_short, ig.instrument_name) AS abbreviation
   FROM v_instrument_groupings ig;

-- ----------------------------
--  View structure for v_proposal_search
-- ----------------------------
DROP VIEW IF EXISTS "v_proposal_search";
CREATE VIEW "v_proposal_search" AS  SELECT p.proposal_id AS id,
    ((('[Proposal '::text || (p.proposal_id)::text) || '] '::text) || (COALESCE(p.title, '<Title Unspecified>'::character varying))::text) AS display_name,
    lower(((p.proposal_id)::text ||
        CASE
            WHEN (p.title IS NOT NULL) THEN ('|'::text || (p.title)::text)
            ELSE ''::text
        END)) AS search_field,
    COALESCE(p.title, '<Proposal Title Unspecified>'::character varying) AS order_field,
    COALESCE((date_part('year'::text, p.actual_end_date))::text, 'Unknown'::text) AS category,
    ('Proposal #'::text || (p.proposal_id)::text) AS abbreviation
   FROM proposals p;

-- ----------------------------
--  View structure for v_user_search
-- ----------------------------
DROP VIEW IF EXISTS "v_user_search";
CREATE VIEW "v_user_search" AS  SELECT u.person_id AS id,
    ((((((('[EUS ID '::text || u.person_id) || '] '::text) ||
        CASE
            WHEN (u.first_name IS NOT NULL) THEN ((u.first_name)::text || ' '::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN (u.last_name IS NOT NULL) THEN ((u.last_name)::text || ' '::text)
            ELSE ''::text
        END) || '&lt;'::text) || (u.email_address)::text) || '&gt;'::text) AS display_name,
    lower(((((u.person_id || '|'::text) ||
        CASE
            WHEN (u.first_name IS NOT NULL) THEN ((u.first_name)::text || ' '::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN (u.last_name IS NOT NULL) THEN ((u.last_name)::text || ' '::text)
            ELSE ''::text
        END) || (u.email_address)::text)) AS search_field,
    ((
        CASE
            WHEN (u.last_name IS NOT NULL) THEN ((u.last_name)::text || ' '::text)
            ELSE ''::text
        END ||
        CASE
            WHEN (u.first_name IS NOT NULL) THEN ((u.first_name)::text || ' '::text)
            ELSE ''::text
        END) || (u.email_address)::text) AS order_field,
    COALESCE("left"(upper((u.last_name)::text), 1), "left"(upper((u.email_address)::text), 1)) AS category,
        CASE
            WHEN ((u.first_name IS NULL) AND (u.last_name IS NULL)) THEN (u.email_address)::text
            ELSE (
            CASE
                WHEN (u.first_name IS NOT NULL) THEN ((u.first_name)::text || ' '::text)
                ELSE ''::text
            END || (
            CASE
                WHEN (u.last_name IS NOT NULL) THEN u.last_name
                ELSE ''::character varying
            END)::text)
        END AS abbreviation
   FROM users u;

-- ----------------------------
--  Primary key structure for table proposal_instruments
-- ----------------------------
ALTER TABLE "proposal_instruments" ADD PRIMARY KEY ("proposal_instrument_id", "instrument_id", "proposal_id") NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ----------------------------
--  Primary key structure for table proposal_members
-- ----------------------------
ALTER TABLE "proposal_members" ADD PRIMARY KEY ("proposal_member_id", "proposal_id", "person_id") NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ----------------------------
--  Primary key structure for table proposals
-- ----------------------------
ALTER TABLE "proposals" ADD PRIMARY KEY ("proposal_id") NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ----------------------------
--  Primary key structure for table instruments
-- ----------------------------
ALTER TABLE "instruments" ADD PRIMARY KEY ("instrument_id") NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ----------------------------
--  Primary key structure for table users
-- ----------------------------
ALTER TABLE "users" ADD PRIMARY KEY ("person_id") NOT DEFERRABLE INITIALLY IMMEDIATE;

