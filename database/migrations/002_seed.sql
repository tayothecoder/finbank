-- seed data for offshore banking platform

-- default admin account (admin@offshore.local / Admin1234)
INSERT INTO `admin` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`)
VALUES (1, 'admin@offshore.local', 'y$AgDCeng2.FF8GP.iurT.8ey5s9lpar4amcXbteB.8NSp98DuXbJgK', 'Admin', 'User', 'super_admin');

-- default settings
INSERT INTO `settings` (`id`, `site_name`, `site_email`, `site_phone`, `site_address`, `site_url`, `currency`)
VALUES (1, 'Offshore Private Union Bank', 'support@offshore.local', '+1-800-000-0001', '123 Finance Street, Zurich, Switzerland', 'http://149.102.131.232/banking', 'USD');

-- default smtp settings (empty, to be configured)
INSERT INTO `smtp_settings` (`id`, `host`, `port`, `username`, `password`, `from_email`, `from_name`)
VALUES (1, '', 587, '', '', 'noreply@offshore.local', 'Offshore Bank');

-- test user account (john@test.com / Test1234, internet_id=3000615625, pin=1234)
INSERT INTO `accounts` (`id`, `internet_id`, `email`, `password_hash`, `pin_hash`, `first_name`, `last_name`, `phone`, `currency`, `checking_balance`, `savings_balance`, `checking_acct_no`, `savings_acct_no`, `status`, `gender`, `dob`, `address`, `state`)
VALUES (
    1,
    '3000615625',
    'john@test.com',
    'y$WSSdmBOY23KQ9ygNrg/a3uVZexKMc5JvbSuifV6n1I117DrJMyFVa',
    'y$R1yLQOpQ.VAnvIcPhRFlF.rOuY.dVlbOP6kuoCFLM7f.hW5wu.yJa',
    'John',
    'Smith',
    '+1-555-0123',
    'USD',
    8993.00,
    20.00,
    '7654456987',
    '3597456900',
    'active',
    'Male',
    '1990-05-15',
    '123 Main Street, New York, NY 10001',
    'New York'
);
