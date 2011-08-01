--
-- (c) 2011 Mandriva, http://www.mandriva.com/
--
-- $Id$
--
-- This file is part of MDS, http://mds.mandriva.org
--
-- MDS is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- MDS is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with Pulse 2; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
-- MA 02110-1301, USA.

START TRANSACTION;

ALTER TABLE `initiator` CHANGE `hostname` `hostname` VARCHAR( 32 ) NOT NULL;
ALTER TABLE `source` CHANGE `hostname` `hostname` VARCHAR( 32 ) NOT NULL;

TRUNCATE version;
INSERT INTO version VALUES( 2 );

COMMIT;
