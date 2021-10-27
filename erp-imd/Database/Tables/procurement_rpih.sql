-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted dan Keterangan
ALTER TABLE imderpdb.procurement_rpih 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE imderpdb.procurement_rpih
ADD COLUMN `deletion` varchar(1000) DEFAULT '';