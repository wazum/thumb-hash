CREATE TABLE sys_file_metadata (
    thumb_hash varchar(255) DEFAULT '' NOT NULL,
    KEY idx_thumb_hash (thumb_hash)
);

CREATE TABLE sys_file_processedfile (
    thumb_hash varchar(255) DEFAULT '' NOT NULL,
    KEY idx_thumb_hash (thumb_hash)
);