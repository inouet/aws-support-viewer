--
-- cases
--
create table cases (
 case_id             text primary key,
 status              text,
 cc_email_addresses  text,
 time_created        text,
 severity_code       text,
 language            text,
 category_code       text,
 service_code        text,
 submitted_by        text,
 display_id          text,
 subject             text,
 account_id          text
);

--
-- communications
--
create table communications (
 id                  integer not null primary key autoincrement,
 case_id             text not null,
 communication_id    text not null unique,
 body                text,
 time_created        text,
 submitted_by        text,
 has_attachment      integer default 0
);

--
-- attachments
--
create table attachments (
 id                  integer not null primary key autoincrement,
 case_id             text not null,
 communication_id    text not null,
 attachment_id       text not null unique,
 file_name           text
);

