-- ISA ANSHORI, 3 MARET 2016, PENAMBAHAN COLUMN sup_nama
ALTER TABLE imderpdb.procurement_rpid 
ADD COLUMN `sup_nama` char(50) DEFAULT '""' AFTER `sup_kode`;

UPDATE imderpdb.procurement_rpid a
INNER JOIN imderpdb.master_suplier b ON (a.sup_kode=b.sup_kode)
SET a.sup_nama = b.sup_nama;

-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted dan Keterangan
ALTER TABLE imderpdb.procurement_rpid 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE imderpdb.procurement_rpid
ADD COLUMN `deletion` varchar(1000) DEFAULT '';

