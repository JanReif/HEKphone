CREATE TABLE asterisk_cdr (id BIGSERIAL, calldate TIMESTAMP DEFAULT 'now()' NOT NULL, clid TEXT NOT NULL, src TEXT NOT NULL, dst TEXT NOT NULL, dcontext TEXT NOT NULL, channel TEXT NOT NULL, dstchannel TEXT NOT NULL, lastapp TEXT NOT NULL, lastdata TEXT NOT NULL, duration BIGINT DEFAULT 0 NOT NULL, billsec BIGINT DEFAULT 0 NOT NULL, disposition TEXT NOT NULL, amaflags BIGINT DEFAULT 0 NOT NULL, accountcode TEXT NOT NULL, uniqueid TEXT NOT NULL UNIQUE, userfield TEXT NOT NULL, billed BOOLEAN DEFAULT 'false' NOT NULL, PRIMARY KEY(id));
CREATE TABLE asterisk_extensions (id SERIAL, context TEXT DEFAULT 'default' NOT NULL, exten TEXT, priority SMALLINT DEFAULT 0 NOT NULL, app TEXT NOT NULL, appdata TEXT DEFAULT NULL, PRIMARY KEY(id));
CREATE TABLE asterisk_sip (id BIGSERIAL, name VARCHAR(80) DEFAULT '' NOT NULL, type VARCHAR(6) DEFAULT 'friend' NOT NULL, callerid VARCHAR(80), defaultuser VARCHAR(80) DEFAULT '' NOT NULL, secret VARCHAR(80), host VARCHAR(31) DEFAULT 'dynamic' NOT NULL, defaultip VARCHAR(15), mac VARCHAR(20) DEFAULT NULL, language VARCHAR(2) DEFAULT 'de', mailbox VARCHAR(50), regserver VARCHAR(20), regseconds VARCHAR(20), ipaddr VARCHAR(15) DEFAULT '' NOT NULL, port VARCHAR(5) DEFAULT '' NOT NULL, fullcontact VARCHAR(80) DEFAULT '' NOT NULL, useragent VARCHAR(20) DEFAULT NULL, lastms VARCHAR(11) DEFAULT NULL, PRIMARY KEY(id));
CREATE TABLE asterisk_voicemail (id BIGSERIAL, uniqueid BIGINT NOT NULL, customer_id VARCHAR(11) DEFAULT '0' NOT NULL, context VARCHAR(50) DEFAULT 'default' NOT NULL, mailbox VARCHAR(11) DEFAULT '0' NOT NULL, password VARCHAR(10) DEFAULT '0' NOT NULL, fullname VARCHAR(150) DEFAULT '' NOT NULL, email VARCHAR(50) NOT NULL, pager VARCHAR(50) DEFAULT '' NOT NULL, tz VARCHAR(10) DEFAULT 'central' NOT NULL, attach VARCHAR(4) DEFAULT 'yes' NOT NULL, saycid VARCHAR(4) DEFAULT 'yes' NOT NULL, dialout VARCHAR(4) DEFAULT '' NOT NULL, callback VARCHAR(10) DEFAULT '' NOT NULL, review VARCHAR(4) DEFAULT 'no' NOT NULL, operator VARCHAR(4) DEFAULT 'no' NOT NULL, envelope VARCHAR(4) DEFAULT 'no' NOT NULL, sayduration VARCHAR(4) DEFAULT 'no' NOT NULL, saydurationm INT DEFAULT 1 NOT NULL, sendvoicemail VARCHAR(4) DEFAULT 'no' NOT NULL, delete VARCHAR(4) DEFAULT 'no' NOT NULL, nextaftercmd VARCHAR(4) DEFAULT 'yes' NOT NULL, forcename VARCHAR(4) DEFAULT 'no' NOT NULL, forcegreetings VARCHAR(4) DEFAULT 'no' NOT NULL, hidefromdir VARCHAR(4) DEFAULT 'yes' NOT NULL, minsecs SMALLINT DEFAULT 3, stamp TIMESTAMP, PRIMARY KEY(id));
CREATE TABLE banks (bank_number VARCHAR(8) UNIQUE, name VARCHAR(80) NOT NULL, zip CHAR(5), locality VARCHAR(80), PRIMARY KEY(bank_number));
CREATE TABLE bills (id BIGSERIAL, resident BIGINT NOT NULL, date DATE NOT NULL, amount NUMERIC(18,2) NOT NULL, debit_failed BOOLEAN DEFAULT 'false' NOT NULL, PRIMARY KEY(id));
CREATE TABLE calls (id BIGSERIAL, resident BIGINT NOT NULL, extension VARCHAR(10) NOT NULL, date TIMESTAMP DEFAULT 'now()', duration VARCHAR(6) NOT NULL, destination VARCHAR(50) NOT NULL, asterisk_uniqueid VARCHAR(30) UNIQUE, charges NUMERIC(18,2) NOT NULL, rate BIGINT NOT NULL, bill BIGINT, PRIMARY KEY(id));
CREATE TABLE comments (id BIGSERIAL, resident BIGINT NOT NULL, stamp TIMESTAMP DEFAULT 'now()' NOT NULL, comment VARCHAR(1000) NOT NULL, PRIMARY KEY(id));
CREATE TABLE groupcalls (id SERIAL, extension TEXT UNIQUE, name TEXT, PRIMARY KEY(id));
CREATE TABLE phones (id BIGSERIAL, technology VARCHAR(20) DEFAULT 'SIP' NOT NULL, name VARCHAR(80) DEFAULT '' NOT NULL, type VARCHAR(6) DEFAULT 'friend' NOT NULL, callerid VARCHAR(80), defaultuser VARCHAR(80) DEFAULT '' NOT NULL, secret VARCHAR(80), host VARCHAR(31) DEFAULT 'dynamic' NOT NULL, defaultip VARCHAR(15), mac VARCHAR(20) DEFAULT NULL, language VARCHAR(2) DEFAULT 'de', mailbox VARCHAR(50), regserver VARCHAR(20), regseconds VARCHAR(20), ipaddr VARCHAR(15) DEFAULT '' NOT NULL, port VARCHAR(5) DEFAULT '' NOT NULL, fullcontact VARCHAR(80) DEFAULT '' NOT NULL, useragent VARCHAR(20) DEFAULT NULL, lastms VARCHAR(11) DEFAULT NULL, PRIMARY KEY(id));
CREATE TABLE prefixes (id BIGSERIAL, prefix VARCHAR(20) NOT NULL, name VARCHAR(80) NOT NULL, region BIGINT NOT NULL, PRIMARY KEY(id));
CREATE TABLE providers (id SMALLINT, name VARCHAR(20) NOT NULL, PRIMARY KEY(id));
CREATE TABLE rates (id BIGINT, provider SMALLINT NOT NULL, primary_time_begin TIME NOT NULL, primary_time_rate NUMERIC(18,2) NOT NULL, secondary_time_begin TIME, secondary_time_rate NUMERIC(18,2), weekend BOOLEAN DEFAULT 'true' NOT NULL, week BOOLEAN DEFAULT 'true' NOT NULL, pulsing VARCHAR(255) NOT NULL, name VARCHAR(80) NOT NULL, PRIMARY KEY(id));
CREATE TABLE rates_regions (id BIGSERIAL, rate BIGINT NOT NULL, region BIGINT, PRIMARY KEY(id));
CREATE TABLE regions (id BIGINT, name VARCHAR(80) NOT NULL UNIQUE, PRIMARY KEY(id));
CREATE TABLE residents (id BIGINT, last_name VARCHAR(50) NOT NULL, first_name VARCHAR(50) NOT NULL, email VARCHAR(255), move_in DATE NOT NULL, move_out DATE, bill_limit INT DEFAULT 75 NOT NULL, room INT, warning1 BOOLEAN DEFAULT 'false', warning2 BOOLEAN DEFAULT 'false', unlocked BOOLEAN DEFAULT 'false', vm_active BOOLEAN DEFAULT 'false' NOT NULL, vm_seconds INT DEFAULT 15 NOT NULL, mail_on_missed_call BOOLEAN DEFAULT 'true' NOT NULL, shortened_itemized_bill BOOLEAN DEFAULT 'true', account_number VARCHAR(10), bank_number VARCHAR(8), password VARCHAR(255), hekphone BOOLEAN DEFAULT 'false', culture VARCHAR(5) DEFAULT 'de_DE', PRIMARY KEY(id));
CREATE TABLE residents_groupcalls (resident_id BIGINT, groupcall_id BIGINT, PRIMARY KEY(resident_id, groupcall_id));
CREATE TABLE rooms (id INT, room_no INT, comment TEXT, phone INT, PRIMARY KEY(id));
CREATE SEQUENCE rooms_id_seq INCREMENT 1 START 1;
CREATE INDEX uniqueid ON asterisk_cdr (uniqueid);
ALTER TABLE bills ADD CONSTRAINT bills_resident_residents_id FOREIGN KEY (resident) REFERENCES residents(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE calls ADD CONSTRAINT calls_resident_residents_id FOREIGN KEY (resident) REFERENCES residents(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE calls ADD CONSTRAINT calls_rate_rates_id FOREIGN KEY (rate) REFERENCES rates(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE calls ADD CONSTRAINT calls_bill_bills_id FOREIGN KEY (bill) REFERENCES bills(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE calls ADD CONSTRAINT calls_asterisk_uniqueid_asterisk_cdr_uniqueid FOREIGN KEY (asterisk_uniqueid) REFERENCES asterisk_cdr(uniqueid) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE comments ADD CONSTRAINT comments_resident_residents_id FOREIGN KEY (resident) REFERENCES residents(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE prefixes ADD CONSTRAINT prefixes_region_regions_id FOREIGN KEY (region) REFERENCES regions(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE rates ADD CONSTRAINT rates_provider_providers_id FOREIGN KEY (provider) REFERENCES providers(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE rates_regions ADD CONSTRAINT rates_regions_rate_rates_id FOREIGN KEY (rate) REFERENCES rates(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE residents ADD CONSTRAINT residents_room_rooms_id FOREIGN KEY (room) REFERENCES rooms(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE residents ADD CONSTRAINT residents_bank_number_banks_bank_number FOREIGN KEY (bank_number) REFERENCES banks(bank_number) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE residents_groupcalls ADD CONSTRAINT residents_groupcalls_resident_id_residents_id FOREIGN KEY (resident_id) REFERENCES residents(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE residents_groupcalls ADD CONSTRAINT residents_groupcalls_groupcall_id_groupcalls_id FOREIGN KEY (groupcall_id) REFERENCES groupcalls(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE rooms ADD CONSTRAINT rooms_phone_phones_id FOREIGN KEY (phone) REFERENCES phones(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE TABLE letztezimmerbelegung (vorname TEXT, nachname TEXT, nutzerid BIGINT, zimmernummer TEXT, einzug DATE, auszug DATE, PRIMARY KEY(nutzerid));
