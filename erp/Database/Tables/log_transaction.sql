-- ISA ANSHORI, 14 JANUARI 2016, Penambahan Column file_before dan file_after untuk merekam file yang dihapus
ALTER TABLE log_transaction 
ADD COLUMN (`file_before` text,`file_after` text);
