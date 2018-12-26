<?php

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']
);


$sql = <<< __HEREDOC__
CREATE TABLE m_authorization (
  access_token character varying(255) NOT NULL
 ,expires_in bigint NOT NULL
 ,refresh_token character varying(255) NOT NULL
 ,scope character varying(255) NOT NULL
 ,create_time timestamp DEFAULT localtimestamp NOT NULL
 ,update_time timestamp DEFAULT localtimestamp NOT NULL
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$sql = <<< __HEREDOC__
CREATE TABLE t_webcache (
    url_base64 character varying(1024) PRIMARY KEY,
    content_compress_base64 text,
    update_time timestamp without time zone DEFAULT LOCALTIMESTAMP NOT NULL
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$sql = <<< __HEREDOC__
CREATE TABLE m_tenki (
    location_number character varying(5) NOT NULL,
    point_name character varying(100) NOT NULL,
    yyyymmdd character varying(8) NOT NULL,
    PRIMARY KEY (location_number, point_name, yyyymmdd)
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$sql = <<< __HEREDOC__
CREATE TABLE t_ical (
    ical_data text
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$sql = <<< __HEREDOC__
CREATE TABLE t_imageparsehash (
    group_id integer NOT NULL,
    hash_text character varying(128) NOT NULL,
    parse_text text NOT NULL,
    update_time timestamp without time zone DEFAULT LOCALTIMESTAMP NOT NULL,
    PRIMARY KEY (group_id, hash_text)
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$sql = <<< __HEREDOC__
CREATE TABLE m_url (
    alias_name character varying(128) PRIMARY KEY,
    url character varying(512) NOT NULL
);
__HEREDOC__;
$count = $pdo->exec($sql);
error_log('create table result : ' . $count);

$pdo = null;
