<?php
// This script populates the database with default values

function populateDatabase($pdo) {
    // Populate profile data
    $stmt = $pdo->prepare('INSERT INTO profile 
        (id, name, job_title, summary, email, phone, location, linkedin, website, github, profile_image) 
        VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        'Prakersh Maheshwari',
        'Software Engineer',
        'I am seeking a position within a professional and dynamic firm where I can leverage my skills and knowledge to contribute to organizational objectives while continuously growing and advancing in my career.',
        'prakersh@live.com',
        '+91 9993556000',
        'Pune, India',
        'linkedin.com/in/prakersh',
        'prakersh.in',
        'github.com/prakersh',
        'assets/images/profile.jpg'
    ]);
    
    // Populate experiences
    $experiences = [
        [
            'Specialist - Software Engineering',
            'LTIMindtree - Microsoft',
            '07/2024',
            'Present',
            'Pune, India',
            json_encode([
                'Worked on Automating and streamlining deployment workflows for HwDiagLnx. Wrote Deployment docs for team to follow.',
                'Deployed released version across clusters and Validate GDCO tickets it created.',
                'Integrated Intel QAT build and sign process in HwDiagLnx. Validated, Tested and End to end integrated Fieldiag for H100, A100 and Jasper.',
                'Created Pipeline for signing kernel modules and rpms for GB200 on Azure Linux 3.',
                'Worked on improving and streamlining build process and restructuring.',
                'Did multiple Linux and Windows deployments.',
                'Worked on implementing PDB diag module in HwDiagLnx.',
                'Worked on analyzing and Implementing fault codes in HwDiagLnx.'
            ])
        ],
        [
            'Member Technical Staff',
            'Coriolis Technologies Pvt. Ltd.',
            '06/2018',
            '02/2024',
            'Pune, India',
            json_encode([
                'Created .rpm/.deb package for the product. Created required install, upgrade and uninstall scripts.',
                'Created systemd/sysvinit service for the product. Managed dependency and service ordering of product.service with dependent services.',
                'Integrated product with redhat pacemaker and wrote pacemaker resource agent to provide high availability for product.',
                'Automated workflows in Linux using python and bash scripts.',
                'Integrated product with Terraform provider. Worked on implementing CTE functionality in Ciphertrust terraform provider.',
                'Developed Multinode execution framework using Redis pub sub and key value store.',
                'Worked on adding additional functionality to existing c binaries based on client requirements.',
                'Implemented upgrade on reboot feature ensuring zero downtime and clean upgraded build post reboot.',
                'Wrote build.sh for products to simplify long build process.',
                'Led Scrum team for Program Increment, facilitating sprint retrospectives and PI evaluations.'
            ])
        ]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO experience 
        (job_title, company, start_date, end_date, location, description) 
        VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($experiences as $exp) {
        $stmt->execute($exp);
    }
    
    // Populate education (example data - adjust based on actual content)
    $education = [
        [
            'B.Tech in Computer Science & Engineering',
            'RGPV University',
            '2014',
            '2018',
            'Bhopal, India',
            json_encode([
                'Graduated with First Class Honors',
                'Specialized in Software Development and System Administration'
            ])
        ]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO education 
        (degree, institution, start_date, end_date, location, description) 
        VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($education as $edu) {
        $stmt->execute($edu);
    }
    
    // Populate skills
    $technicalSkills = [
        ['Technical Skills', 'Python', 5],
        ['Technical Skills', 'Shell Scripting', 5],
        ['Technical Skills', 'REDIS', 4],
        ['Technical Skills', 'Systemd', 4],
        ['Technical Skills', 'Automation', 5],
        ['Technical Skills', 'C/C++', 4],
        ['Technical Skills', 'Golang', 3],
        ['Technical Skills', 'REST API', 4],
        ['Technical Skills', 'Git', 4],
        ['Technical Skills', 'SVN', 3]
    ];
    
    $platformSkills = [
        ['Platforms & Tools', 'Ansible', 4],
        ['Platforms & Tools', 'PSSH', 4],
        ['Platforms & Tools', 'MySQL', 3],
        ['Platforms & Tools', 'RHEL/CentOS', 5],
        ['Platforms & Tools', 'Ubuntu', 4],
        ['Platforms & Tools', 'Pacemaker', 4],
        ['Platforms & Tools', 'Linux Networking', 4],
        ['Platforms & Tools', 'System Administration', 5]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO skills 
        (category, name, level) 
        VALUES (?, ?, ?)');
    
    foreach (array_merge($technicalSkills, $platformSkills) as $skill) {
        $stmt->execute($skill);
    }
    
    // Populate achievements with actual data
    $achievements = [
        [
            'Indian Association of Physics Teachers (2013 - 2014)',
            'Certificate of Merit for being in national top 1% in national standard examination in physics',
            '2014'
        ],
        [
            'International Mathematics Olympiad (2013)',
            'International Rank: 8, State Rank: 2',
            '2013'
        ],
        [
            'National Science Olympiad (2013)',
            'International Rank: 9, State Rank: 3',
            '2013'
        ],
        [
            'PyCon India (10/2015)',
            'Python developer community - The premier conference in India on using and developing the Python programming language',
            '2015'
        ]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO achievements 
        (title, description, date) 
        VALUES (?, ?, ?)');
    
    foreach ($achievements as $achievement) {
        $stmt->execute($achievement);
    }
    
    // Populate projects with actual data
    $projects = [
        [
            'Reader Writer Lock',
            'A C-based implementation of a reader-writer lock for file sharing over NFS. Supports multiple concurrent readers with exclusive writer access.',
            json_encode(['C', 'NFS', 'POSIX']),
            'https://github.com/prakersh/reader-writer-lock',
            ''
        ],
        [
            'encr - Encryption Tool',
            'A Shell-based wrapper over OpenSSL that provides an easy-to-use interface for file encryption and decryption with simple command line parameters.',
            json_encode(['Shell', 'OpenSSL', 'Bash']),
            'https://github.com/prakersh/encr',
            ''
        ],
        [
            'Python Progress Bar',
            'An implementation example of progress bars in Python for providing visual feedback to users during long-running operations.',
            json_encode(['Python', 'CLI', 'Utility']),
            'https://github.com/prakersh/progressbar-python',
            ''
        ],
        [
            'ShortTouch',
            'A Python utility that enables quick interactions with your system through customizable shortcuts and automation features.',
            json_encode(['Python', 'Automation', 'Utility']),
            'https://github.com/prakersh/shorttouch',
            ''
        ],
        [
            'Open Source Point of Sale',
            'A PHP web application using CodeIgniter for managing inventory, sales, and customers with a responsive interface.',
            json_encode(['PHP', 'CodeIgniter', 'MySQL']),
            'https://github.com/prakersh/opensourcepos',
            ''
        ],
        [
            'Portfolio Website',
            'A responsive PHP portfolio/resume website with print functionality. Features modern design with Bootstrap and comprehensive resume sections.',
            json_encode(['CSS', 'PHP', 'Bootstrap']),
            'https://github.com/prakersh/prakersh.in',
            ''
        ]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO projects 
        (title, description, technologies, link, image) 
        VALUES (?, ?, ?, ?, ?)');
    
    foreach ($projects as $project) {
        $stmt->execute($project);
    }
}
?> 