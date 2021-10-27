-- ICHA MAILINDA, 27 Mei 2016, UPDATE column genoprj, transengineer_detilwork, brggeno, nonmaster, ket, cus_kode, tranorev, customercontract
UPDATE transengineer_boq3d 
SET 
    genoprj = '""',
    transengineer_detilwork = '""',
    brggeno = '""',
    nonmaster = '""',
    ket = '""',
    cus_kode = '""',
    tranorev = '""',
    customercontract = '0.0000'
WHERE
    prj_kode = 'Q000166';
