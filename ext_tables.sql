#
# Add SQL definition of database tables
#

CREATE TABLE tx_importer_domain_model_job
(
	start_time     int(11)      DEFAULT '0' NOT NULL,
	end_time       int(11)      DEFAULT '0' NOT NULL,
	is_finisher    tinyint(4)   DEFAULT '0' NOT NULL,
	status         varchar(255) DEFAULT ''  NOT NULL,
	payload        text,
	payload_type   varchar(255) DEFAULT ''  NOT NULL,
	failure_reason text,
	import         int(11)      DEFAULT '0' NOT NULL,
	INDEX status (status),
	INDEX import (import),
	INDEX sorting (sorting)
);

CREATE TABLE tx_importer_domain_model_import
(
	status         varchar(255) DEFAULT ''  NOT NULL,
	start_time     int(11)      DEFAULT '0' NOT NULL,
	end_time       int(11)      DEFAULT '0' NOT NULL,
	import_type    varchar(255) DEFAULT ''  NOT NULL,
	failed_jobs    int(11)      DEFAULT '0' NOT NULL,
	completed_jobs int(11)      DEFAULT '0' NOT NULL,
	total_jobs     int(11)      DEFAULT '0' NOT NULL,
	statistics     int(11)      DEFAULT '0' NOT NULL
);

CREATE TABLE tx_importer_domain_model_statistic
(
	import           int(11)      DEFAULT '0' NOT NULL,
	record_name      varchar(255) DEFAULT ''  NOT NULL,
	record_table     varchar(255) DEFAULT ''  NOT NULL,
	number_added     int(11)      DEFAULT '0' NOT NULL,
	number_updated   int(11)      DEFAULT '0' NOT NULL,
	number_deleted   int(11)      DEFAULT '0' NOT NULL,
	number_unchanged int(11)      DEFAULT '0' NOT NULL,

	INDEX import (import),
	INDEX record_table (record_table)
);

CREATE TABLE be_users
(
	importer_failed_notification int(11) DEFAULT '0' NOT NULL
);
