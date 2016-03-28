DROP VIEW IF EXISTS "eus"."v_instrument_groupings";
CREATE VIEW "eus"."v_instrument_groupings" AS SELECT
        CASE
            WHEN "position"(i.instrument_name::text, ':'::text) > 0 THEN rtrim("substring"(i.instrument_name::text, 1, "position"(i.instrument_name::text, ':'::text)), ':'::text)
            ELSE 'Miscellaneous'::text
        END AS instrument_grouping,
        CASE
            WHEN "position"(i.instrument_name::text, ':'::text) > 0 THEN ltrim("substring"(i.instrument_name::text, "position"(i.instrument_name::text, ':'::text)), ' :'::text)::character varying
            ELSE i.instrument_name
        END AS instrument_name,
    i.instrument_id,
        CASE
            WHEN "position"(i.name_short::text, ':'::text) > 0 THEN ltrim("substring"(i.name_short::text, "position"(i.name_short::text, ':'::text)), ' :'::text)::character varying
            ELSE i.name_short
        END AS name_short
   FROM eus.instruments i
  WHERE i.active_sw = 'Y'::bpchar
  ORDER BY
        CASE
            WHEN "position"(i.instrument_name::text, ':'::text) > 0 THEN rtrim("substring"(i.instrument_name::text, 1, "position"(i.instrument_name::text, ':'::text)), ':'::text)
            ELSE 'Miscellaneous'::text
        END,
        CASE
            WHEN "position"(i.instrument_name::text, ':'::text) > 0 THEN ltrim("substring"(i.instrument_name::text, "position"(i.instrument_name::text, ':'::text)), ' :'::text)::character varying
            ELSE i.instrument_name
        END;

DROP VIEW IF EXISTS "eus"."v_instrument_search";
CREATE VIEW "eus"."v_instrument_search" AS SELECT ig.instrument_id AS id,
    (((('['::text || ig.instrument_grouping) || ' / ID:'::text) || ig.instrument_id) || '] '::text) || ig.instrument_name::text AS display_name,
    lower((((((ig.instrument_grouping || '|'::text) || ig.instrument_id) || '|'::text) || ig.instrument_name::text) || '|'::text) || ig.name_short::text) AS search_field,
    (ig.instrument_grouping || '|'::text) || ig.instrument_name::text AS order_field,
    ig.instrument_grouping AS category,
    ig.name_short AS abbreviation
   FROM eus.v_instrument_groupings ig;

DROP VIEW IF EXISTS "eus"."v_proposal_search";
CREATE VIEW "eus"."v_proposal_search" AS SELECT p.proposal_id AS id,
    (('[Proposal '::text || p.proposal_id::text) || '] '::text) || p.title::text AS display_name,
    lower((p.proposal_id::text || '|'::text) || p.title::text) AS search_field,
    p.title AS order_field,
    COALESCE(date_part('year'::text, p.actual_end_date)::text, 'Unknown'::text) AS category,
    ('[Proposal #'::text || p.proposal_id::text) || ']'::text AS abbreviation
   FROM eus.proposals p;

DROP VIEW IF EXISTS "eus"."v_user_search";
CREATE VIEW "eus"."v_user_search" AS SELECT u.person_id AS id,
    ((((((('[EUS ID '::text || u.person_id) || '] '::text) || u.first_name::text) || ' '::text) || u.last_name::text) || ' &lt;'::text) || u.email_address::text) || '&gt;'::text AS display_name,
    lower((((((u.person_id || '|'::text) || u.first_name::text) || '|'::text) || u.last_name::text) || '|'::text) || u.email_address::text) AS search_field,
    (((u.last_name::text || ' | '::text) || u.first_name::text) || ' | '::text) || u.email_address::text AS order_field,
    "left"(upper(u.last_name::text), 1) AS category,
    (u.first_name::text || ' '::text) || u.last_name::text AS abbreviation
   FROM eus.users u;

