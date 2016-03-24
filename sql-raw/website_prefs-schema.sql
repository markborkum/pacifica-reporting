--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: website_prefs; Type: SCHEMA; Schema: -; Owner: metadata_admins
--

CREATE SCHEMA website_prefs;


ALTER SCHEMA website_prefs OWNER TO metadata_admins;

SET search_path = website_prefs, pg_catalog;

--
-- Name: set_updated_time(); Type: FUNCTION; Schema: website_prefs; Owner: metadata_admins
--

CREATE FUNCTION set_updated_time() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$BEGIN
    NEW.updated = now();
    RETURN NEW;
END;$$;


ALTER FUNCTION website_prefs.set_updated_time() OWNER TO metadata_admins;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: reporting_object_group_option_defaults; Type: TABLE; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

CREATE TABLE reporting_object_group_option_defaults (
    option_type character varying NOT NULL,
    option_default character varying NOT NULL,
    created timestamp(6) with time zone DEFAULT now() NOT NULL,
    updated timestamp(6) with time zone NOT NULL,
    deleted timestamp(6) with time zone
);


ALTER TABLE reporting_object_group_option_defaults OWNER TO metadata_admins;

--
-- Name: reporting_object_group_options; Type: TABLE; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

CREATE TABLE reporting_object_group_options (
    group_id bigint NOT NULL,
    option_type character varying NOT NULL,
    option_value character varying NOT NULL,
    created timestamp(6) with time zone DEFAULT now() NOT NULL,
    updated timestamp(6) with time zone DEFAULT now(),
    deleted timestamp(6) with time zone
);


ALTER TABLE reporting_object_group_options OWNER TO metadata_admins;

--
-- Name: reporting_object_groups; Type: TABLE; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

CREATE TABLE reporting_object_groups (
    group_id bigint NOT NULL,
    person_id integer NOT NULL,
    group_name character varying NOT NULL,
    group_type character varying NOT NULL,
    ordering integer DEFAULT 0 NOT NULL,
    created timestamp(6) with time zone DEFAULT now() NOT NULL,
    updated timestamp(6) with time zone DEFAULT now(),
    deleted timestamp(6) with time zone
);


ALTER TABLE reporting_object_groups OWNER TO metadata_admins;

--
-- Name: reporting_object_groups_group_id_seq; Type: SEQUENCE; Schema: website_prefs; Owner: metadata_admins
--

CREATE SEQUENCE reporting_object_groups_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE reporting_object_groups_group_id_seq OWNER TO metadata_admins;

--
-- Name: reporting_object_groups_group_id_seq; Type: SEQUENCE OWNED BY; Schema: website_prefs; Owner: metadata_admins
--

ALTER SEQUENCE reporting_object_groups_group_id_seq OWNED BY reporting_object_groups.group_id;


--
-- Name: reporting_selection_prefs; Type: TABLE; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

CREATE TABLE reporting_selection_prefs (
    eus_person_id integer NOT NULL,
    item_type character varying NOT NULL,
    item_id character varying NOT NULL,
    group_id integer DEFAULT 0 NOT NULL,
    created timestamp(6) with time zone DEFAULT now() NOT NULL,
    updated timestamp(6) with time zone NOT NULL,
    deleted timestamp(6) with time zone
);


ALTER TABLE reporting_selection_prefs OWNER TO metadata_admins;

--
-- Name: group_id; Type: DEFAULT; Schema: website_prefs; Owner: metadata_admins
--

ALTER TABLE ONLY reporting_object_groups ALTER COLUMN group_id SET DEFAULT nextval('reporting_object_groups_group_id_seq'::regclass);


--
-- Name: reporting_object_group_option_defaults_pkey; Type: CONSTRAINT; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

ALTER TABLE ONLY reporting_object_group_option_defaults
    ADD CONSTRAINT reporting_object_group_option_defaults_pkey PRIMARY KEY (option_type, option_default);


--
-- Name: reporting_object_group_options_pkey; Type: CONSTRAINT; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

ALTER TABLE ONLY reporting_object_group_options
    ADD CONSTRAINT reporting_object_group_options_pkey PRIMARY KEY (group_id, option_type);


--
-- Name: reporting_object_groups_pkey; Type: CONSTRAINT; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

ALTER TABLE ONLY reporting_object_groups
    ADD CONSTRAINT reporting_object_groups_pkey PRIMARY KEY (group_id);


--
-- Name: reporting_selection_prefs_new_pkey; Type: CONSTRAINT; Schema: website_prefs; Owner: metadata_admins; Tablespace: 
--

ALTER TABLE ONLY reporting_selection_prefs
    ADD CONSTRAINT reporting_selection_prefs_new_pkey PRIMARY KEY (eus_person_id, item_type, item_id, group_id);


--
-- Name: trg_rog_opt_set_updated; Type: TRIGGER; Schema: website_prefs; Owner: metadata_admins
--

CREATE TRIGGER trg_rog_opt_set_updated BEFORE INSERT OR UPDATE ON reporting_object_group_options FOR EACH ROW EXECUTE PROCEDURE set_updated_time();


--
-- Name: trg_rogod_set_updated; Type: TRIGGER; Schema: website_prefs; Owner: metadata_admins
--

CREATE TRIGGER trg_rogod_set_updated BEFORE INSERT OR UPDATE ON reporting_object_group_option_defaults FOR EACH ROW EXECUTE PROCEDURE set_updated_time();


--
-- Name: trg_rsp_set_updated; Type: TRIGGER; Schema: website_prefs; Owner: metadata_admins
--

CREATE TRIGGER trg_rsp_set_updated BEFORE INSERT OR UPDATE ON reporting_selection_prefs FOR EACH ROW EXECUTE PROCEDURE set_updated_time();


--
-- Name: trg_rsp_set_updated; Type: TRIGGER; Schema: website_prefs; Owner: metadata_admins
--

CREATE TRIGGER trg_rsp_set_updated BEFORE INSERT OR UPDATE ON reporting_object_groups FOR EACH ROW EXECUTE PROCEDURE set_updated_time();


--
-- PostgreSQL database dump complete
--

