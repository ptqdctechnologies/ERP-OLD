-- ICHA MAILINDA, 28 Juni 2016, Insert into erpdb.menu
INSERT INTO erpdb.menu (id, module_name, text, id_parent, id_name, link, active, leaf, type, level) VALUES ('370', 'procurement', 'PR to DOR', '174', 'procurement-prtodor', '/default/report/showprdor', '1', 'true', 'REPORT', '2');


-- ICHA MAILINDA, 15 Juni 2016, Update Column link
UPDATE erpdb.menu SET link='/default/report/showporpisummarynew' WHERE id='184';

