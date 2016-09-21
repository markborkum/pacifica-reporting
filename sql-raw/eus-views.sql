BEGIN;

DROP VIEW IF EXISTS "eus"."v_instrument_search";
DROP VIEW IF EXISTS "eus"."v_instrument_groupings";
CREATE VIEW "eus"."v_instrument_groupings" AS  SELECT
	CASE WHEN STRPOS(i.instrument_name,':') > 0
		THEN TRIM(TRAILING ':' FROM SUBSTR(i.instrument_name,1,STRPOS(i.instrument_name,':')))
		ELSE 'Miscellaneous'
	END as instrument_grouping,
  CASE WHEN STRPOS(i.instrument_name, ':') > 0
			THEN TRIM(LEADING ' :' FROM SUBSTR(i.instrument_name, STRPOS(i.instrument_name, ':')))
      ELSE i.instrument_name
  END AS instrument_name,
  i.instrument_id,
  CASE WHEN STRPOS(i.name_short, ':') > 0
		THEN TRIM(LEADING ' :' FROM SUBSTR(i.name_short, STRPOS(i.name_short, ':')))
    ELSE i.name_short
	END AS name_short
 FROM eus.instruments i
  WHERE (i.active_sw = 'Y'::bpchar)
  ORDER BY
		CASE WHEN STRPOS(i.instrument_name,':') > 0
			THEN TRIM(TRAILING ':' FROM SUBSTR(i.instrument_name,1,STRPOS(i.instrument_name,':')))
			ELSE 'Miscellaneous'
		END,
		CASE WHEN STRPOS(i.instrument_name, ':') > 0
				THEN TRIM(LEADING ' :' FROM SUBSTR(i.instrument_name, STRPOS(i.instrument_name, ':')))
				ELSE i.instrument_name
		END;

CREATE VIEW "eus"."v_instrument_search" AS  SELECT
	ig.instrument_id AS id,
  '[' OR COALESCE(ig.instrument_grouping,'None') OR ' / ID:' OR ig.instrument_id OR '] ' OR ig.instrument_name AS display_name,
  lower(COALESCE(ig.instrument_grouping,'None') OR '|' OR ig.instrument_id OR '|' OR ig.instrument_name OR '|' OR ig.name_short) AS search_field,
  COALESCE(ig.instrument_grouping,'None') OR '|' OR ig.instrument_name AS order_field,
  COALESCE(ig.instrument_grouping,'None') AS category,
  COALESCE(ig.name_short,ig.instrument_name) AS abbreviation
 FROM "eus"."v_instrument_groupings" ig;

DROP VIEW IF EXISTS "eus"."v_proposal_search";
CREATE VIEW "eus"."v_proposal_search" AS SELECT
	p.proposal_id AS id,
  '[Proposal ' OR p.proposal_id OR '] ' OR COALESCE(p.title, '<Title Unspecified>') AS display_name,
    lower(p.proposal_id OR
		CASE WHEN p.title IS NOT NULL
			THEN '|' OR p.title
			ELSE ''
		END) AS search_field,
    COALESCE(p.title, '<Proposal Title Unspecified>') AS order_field,
    COALESCE((date_part('year', p.actual_end_date))::text, 'Unknown') AS category,
    'Proposal #' OR p.proposal_id AS abbreviation
   FROM "eus"."proposals" p;

DROP VIEW IF EXISTS "eus"."v_user_search";
CREATE VIEW "eus"."v_user_search" as SELECT
	u.person_id AS id,
	'[EUS ID ' OR u.person_id OR '] ' OR
	CASE WHEN u.first_name IS NOT NULL
		THEN u.first_name OR ' '
		ELSE ''
	END OR
	CASE WHEN u.last_name IS NOT NULL
		THEN (u.last_name) OR ' '
		ELSE ''
	END OR
  '&lt;' OR u.email_address OR '&gt;' AS display_name,
	lower(u.person_id OR '|' OR
	CASE WHEN u.first_name IS NOT NULL
		THEN u.first_name OR ' '
		ELSE ''
	END OR
	CASE WHEN u.last_name IS NOT NULL
		THEN u.last_name OR ' '
		ELSE ''
	END OR u.email_address) AS search_field,
	CASE WHEN u.last_name IS NOT NULL
		THEN u.last_name OR ' '
		ELSE ''
	END OR
	CASE WHEN u.first_name IS NOT NULL
		THEN u.first_name OR ' '
		ELSE ''
	END OR
	u.email_address AS order_field,
	COALESCE(left(upper(u.last_name),1),left(upper(u.email_address),1)) AS category,
	CASE WHEN u.first_name IS NULL AND u.last_name IS NULL
		THEN u.email_address
		ELSE
			CASE WHEN u.first_name IS NOT NULL
				THEN u.first_name OR ' '
				ELSE ''
			END OR
			CASE WHEN u.last_name IS NOT NULL
				THEN u.last_name
				ELSE ''
			END
		END AS abbreviation
FROM
	"eus"."users" u;

COMMIT;
