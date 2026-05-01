-- Seed all 50 specialty contractor categories

INSERT INTO categories (name, slug, description, icon, group_name, sort_order) VALUES

-- Standard Trades
('Electrical',              'electrical',           'Wiring, panels, lighting, and low-voltage systems',                          '⚡', 'Standard Trades', 1),
('Plumbing',                'plumbing',             'Water supply, drainage, fixtures, and gas lines',                            '🔧', 'Standard Trades', 2),
('HVAC & Mechanical',       'hvac',                 'Heating, ventilation, air conditioning, and ductwork',                       '❄️', 'Standard Trades', 3),
('Roofing',                 'roofing',              'Shingles, flat roofs, metal roofing, and waterproofing',                     '🏠', 'Standard Trades', 4),
('Concrete & Masonry',      'concrete-masonry',     'Foundations, slabs, brick, block, and stone work',                          '🧱', 'Standard Trades', 5),
('Framing & Carpentry',     'framing-carpentry',    'Rough framing and finish carpentry',                                         '🪚', 'Standard Trades', 6),
('Drywall',                 'drywall',              'Hanging, taping, and finishing drywall',                                     '🏗️', 'Standard Trades', 7),
('Painting',                'painting',             'Interior, exterior, and specialty coatings',                                 '🎨', 'Standard Trades', 8),
('Flooring',                'flooring',             'Tile, hardwood, carpet, vinyl, and epoxy flooring',                         '🪵', 'Standard Trades', 9),
('Glazing',                 'glazing',              'Windows, glass walls, and storefronts',                                      '🪟', 'Standard Trades', 10),
('Insulation',              'insulation',           'Fiberglass, spray foam, and rigid board insulation',                        '🌡️', 'Standard Trades', 11),
('Landscaping & Hardscaping','landscaping',         'Grading, planting, irrigation, and pavers',                                 '🌿', 'Standard Trades', 12),
('Excavation & Earthwork',  'excavation',           'Grading, trenching, and site preparation',                                  '🚜', 'Standard Trades', 13),
('Steel & Structural',      'steel-structural',     'Steel erection, welding, and rebar work',                                   '🔩', 'Standard Trades', 14),
('Fire Protection',         'fire-protection',      'Sprinkler systems, fire alarms, and suppression systems',                   '🔥', 'Standard Trades', 15),
('Demolition',              'demolition',           'Selective or full building teardown',                                       '💥', 'Standard Trades', 16),
('Asphalt & Paving',        'asphalt-paving',       'Driveways, parking lots, and road paving',                                  '🛣️', 'Standard Trades', 17),
('Solar & Renewable Energy','solar',                'PV installation and battery storage systems',                               '☀️', 'Standard Trades', 18),
('Elevators',               'elevators',            'Elevator installation and service',                                         '🛗', 'Standard Trades', 19),
('Low-Voltage & Data',      'low-voltage-data',     'Security systems, AV, networking, and fiber optic',                        '📡', 'Standard Trades', 20),

-- Specialty Services
('Trash Compactor Repair',  'trash-compactor-repair','Commercial and residential trash compactor repair',                        '🗑️', 'Specialty Services', 21),
('Garage Door Technicians', 'garage-door',          'Spring, opener, and panel repair and installation',                        '🚪', 'Specialty Services', 22),
('Septic System Specialists','septic',              'Septic system installation, pumping, and repair',                           '💧', 'Specialty Services', 23),
('Well Drillers & Pump Techs','well-drilling',      'Water well drilling, pump installation, and pressure tanks',               '⛏️', 'Specialty Services', 24),
('Chimney Sweeps & Masons', 'chimney',              'Chimney cleaning, relining, and masonry repair',                           '🏚️', 'Specialty Services', 25),
('Foundation Repair',       'foundation-repair',    'Piering, slab jacking, and crack injection',                              '🏛️', 'Specialty Services', 26),
('Waterproofing',           'waterproofing',        'Basement, crawl space, and French drain waterproofing',                   '💦', 'Specialty Services', 27),
('Pest Exclusion & Wildlife Removal','pest-exclusion','Sealing entry points, bat and bird proofing',                           '🐾', 'Specialty Services', 28),
('Mold Remediation',        'mold-remediation',     'Mold containment, removal, and air scrubbing',                            '🧫', 'Specialty Services', 29),
('Asbestos & Lead Abatement','asbestos-abatement',  'Licensed hazardous material removal and disposal',                        '⚠️', 'Specialty Services', 30),
('Radon Mitigation',        'radon-mitigation',     'Sub-slab depressurization systems for radon removal',                     '☢️', 'Specialty Services', 31),
('Dock & Seawall Builders', 'dock-seawall',         'Marine construction, docks, and seawall installation',                    '⚓', 'Specialty Services', 32),
('Pool & Spa Technicians',  'pool-spa',             'Pool and spa installation, resurfacing, and equipment repair',             '🏊', 'Specialty Services', 33),
('Awning & Canopy Installers','awning-canopy',      'Retractable, fixed, and commercial awning installation',                  '⛱️', 'Specialty Services', 34),
('Sign Installers',         'sign-installers',      'Channel letters, monument signs, and billboard installation',              '📋', 'Specialty Services', 35),
('Window Tinting & Film',   'window-tinting',       'Residential, commercial, and security window film',                       '🔲', 'Specialty Services', 36),
('Epoxy Flooring Specialists','epoxy-flooring',     'Garage, industrial, and decorative epoxy flooring',                       '✨', 'Specialty Services', 37),
('Gutter Installers',       'gutters',              'Seamless gutters and leaf guard installation',                            '🌧️', 'Specialty Services', 38),
('Stucco & EIFS Contractors','stucco-eifs',         'Stucco and exterior insulation and finish system application and repair',  '🏠', 'Specialty Services', 39),
('Fence Builders',          'fencing',              'Wood, vinyl, chain link, and ornamental iron fencing',                    '🚧', 'Specialty Services', 40),

-- Heavy & Industrial
('Crane Operators & Riggers','crane-rigging',       'Heavy equipment lifting and placement services',                           '🏗️', 'Heavy & Industrial', 41),
('Shoring & Scaffolding',   'shoring-scaffolding',  'Temporary support systems for construction',                              '🦺', 'Heavy & Industrial', 42),
('Sandblasting & Dustless Blasting','sandblasting', 'Surface preparation via sandblasting and dustless blasting',              '💨', 'Heavy & Industrial', 43),
('Acoustic Ceiling Installers','acoustic-ceilings', 'Drop ceilings and sound panel installation',                              '🔊', 'Heavy & Industrial', 44),
('Dumbwaiter & Lift Installers','dumbwaiter-lifts', 'Residential and commercial dumbwaiter installation',                      '📦', 'Heavy & Industrial', 45),

-- Compliance & Inspection
('Commercial Locksmiths',   'commercial-locksmith', 'Access control systems and master key solutions',                         '🔐', 'Compliance & Inspection', 46),
('Fire & Smoke Damper Testing','damper-testing',    'Code compliance testing for HVAC fire and smoke dampers',                 '🚨', 'Compliance & Inspection', 47),
('Backflow Preventer Testers','backflow-testing',   'Certified backflow prevention device testing and repair',                 '🔄', 'Compliance & Inspection', 48),
('Tank Installers',         'tank-installers',      'Fuel, water, propane, and underground storage tank installation',         '⛽', 'Compliance & Inspection', 49),
('Hardscape & Paver Specialists','hardscape-pavers','Patio, walkway, and retaining wall installation using pavers and stone',  '🪨', 'Compliance & Inspection', 50);
