-- =========================================================
-- uni wise — reference data: world regions + central application portals.
-- Run after schema.sql. University data goes in separately (see README).
-- Portal "opens" / "typical_deadlines" are typical windows — verify on each
-- portal's official site each year.
-- =========================================================
USE uniwise;
INSERT INTO regions (id, name, slug, sort_order) VALUES
 (1, 'North America', 'north-america', 1),
 (2, 'UK & Ireland',  'uk-ireland',    2),
 (3, 'Europe',        'europe',        3),
 (4, 'Nordics',       'nordics',       4),
 (5, 'Asia',          'asia',          5),
 (6, 'Oceania',       'oceania',       6),
 (7, 'Middle East',   'middle-east',   7),
 (8, 'Latin America', 'latin-america', 8),
 (9, 'Africa',        'africa',        9);

-- Portal dates are typical windows only
INSERT INTO portals (id, region_id, code, name, scope, description, opens, typical_deadlines, website, sort_order) VALUES
 (1, 1, 'CA', 'Common App', 'United States · 1,000+ colleges', 'One application for many US colleges, with a main essay plus college-specific supplements.', 'August', 'Nov (early) – Jan', 'https://www.commonapp.org', 1),
 (2, 1, 'OU', 'OUAC', 'Ontario, Canada · all universities', 'Central portal for Ontario universities. Other provinces usually apply directly.', 'Autumn', 'January', 'https://www.ouac.on.ca', 2),
 (3, 2, 'UC', 'UCAS', 'United Kingdom · all universities', 'Up to 5 choices with one personal statement. Earlier deadline for Oxford, Cambridge and most medicine courses.', 'September', 'Oct (Oxbridge) · Jan', 'https://www.ucas.com', 1),
 (4, 2, 'CAO', 'CAO', 'Ireland · undergraduate courses', 'Rank course choices in order of preference; offers are based on final exam points.', 'November', 'February', 'https://www.cao.ie', 2),
 (5, 3, 'NL', 'Studielink', 'Netherlands · all universities', 'Register here first, then finish each university''s own steps.', 'October', 'Jan (selective) · May', 'https://www.studielink.nl', 1),
 (6, 3, 'DE', 'uni-assist', 'Germany · many universities', 'Checks international qualifications for many German universities.', 'Varies', 'Jul (winter) · Jan (summer)', 'https://www.uni-assist.de', 2),
 (7, 3, 'FR', 'Parcoursup', 'France · first-year higher education', 'National platform for first-year programmes in France.', 'January', 'March', 'https://www.parcoursup.gouv.fr', 3),
 (8, 4, 'SE', 'Universityadmissions.se', 'Sweden · English-taught programmes', 'Rank several programmes in one application.', 'October', 'January', 'https://www.universityadmissions.se', 1),
 (9, 4, 'FI', 'Studyinfo.fi', 'Finland · joint application', 'Finland''s joint application system.', 'January', 'January', 'https://studyinfo.fi', 2),
 (10, 5, 'HK', 'JUPAS', 'Hong Kong · local curriculum students', 'For HKDSE students; others usually apply directly.', 'Autumn', 'December', 'https://www.jupas.edu.hk', 1),
 (11, 6, 'UAC', 'UAC', 'New South Wales & ACT, Australia', 'Central admissions for universities in NSW and the ACT.', 'August', 'Sep – Jan', 'https://www.uac.edu.au', 1),
 (12, 6, 'VT', 'VTAC', 'Victoria, Australia', 'Central admissions for Victorian universities and TAFEs.', 'August', 'Sep – Dec', 'https://www.vtac.edu.au', 2);
