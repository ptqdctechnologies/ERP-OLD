-- ISA ANSHORI, 6 JANUARY 2016, Penambahan Kolom statusreceiving dan statuspayment2 untuk dropdown
ALTER TABLE `master_countertransaksi` 
ADD COLUMN `statusreceiving` tinyint(1) DEFAULT '0';

ALTER TABLE `master_countertransaksi` 
ADD COLUMN `statuspayment2` tinyint(1) DEFAULT '0';
