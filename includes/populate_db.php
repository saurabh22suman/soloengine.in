<?php
// This script populates the database with default values

function populateDatabase($pdo) {    // Populate profile data
    $stmt = $pdo->prepare('INSERT INTO profile 
        (id, name, job_title, summary, email, phone, location, linkedin, website, github, profile_image) 
        VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        'Saurabh Suman',
        'Data Engineer',
        'Data Engineer with 6+ years of experience in building large-scale data pipelines, ELT processes, and data warehouse solutions. Utilized technologies like Python, SQL, PySpark, Databricks to develop multi-terabyte scalable big data solutions.',
        'soloengine007@gmail.com',
        '',
        'Pune, India',
        'linkedin.com/in/saurabh22suman',
        'soloengine.in',
        'github.com/saurabh22suman',
        'assets/images/profile.jpg'
    ]);
      // Populate experiences
    $experiences = [
        [
            'Application Developer',
            'Fujitsu India Pvt. Ltd.',
            '01/2025',
            'Present',
            'Pune, India',
            json_encode([
                'Developed Azure Logic Apps to automatically detect and respond to pipeline failures, enhancing system resilience and reducing manual intervention.',
                'Designed an optimized data processing workflow from Self-Hosted integration to the gold layer, reducing average latency by 10 hours and operational costs by 15%.',
                'Developed Script to automatically fetch the incremental API data without need to know number of Pages to reduce the pipeline failure and incorrect data capture.'
            ])
        ],
        [
            'Technical Lead- I',
            'Citiustech Healthcare Technology',
            '09/2024',
            '01/2025',
            'Pune, India',
            json_encode([
                'Led onboarding of on-premise carrier data to the bronze and silver layers of the data lake, ensuring accurate and compliant ingestion.'
            ])
        ],
        [
            'Data Scientist',
            'Tiger Analytics',
            '06/2022',
            '09/2024',
            'Chennai, India',
            json_encode([
                'Collaborated closely with business teams to translate requirements into scalable ETL solutions, delivering high-impact data products.',
                'Reduced region-specific solution development time by 30% through modular and reusable code design.',
                'Enhanced pipeline reliability by 60% via robust validation checks across all stages of data flow, and implemented alerting mechanisms for data anomalies.',
                'Led a team with a focus on delivery excellence, mentorship, and knowledge sharing to uplift overall team productivity.'
            ])
        ],
        [
            'Specialist Programmer',
            'Infosys',
            '06/2018',
            '06/2022',
            'Pune, India',
            json_encode([
                'Delivered automated ETL pipelines across diverse domains including Subscription Services and Banking, ensuring data quality and timely availability.',
                'Developed a Python-based automation tool for background check document generation, reducing manual effort by 85%.',
                'Recognized with multiple Infosys Insta Awards; fast-tracked through three promotions for consistent high performance.'
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
            'Bachelor of Engineering in Information Technology',
            'Jabalpur Engineering College',
            '2014',
            '2018',
            'Jabalpur, M.P.',
            json_encode([
                'Graduated with Honors',
                'Specialized in Information Technology'
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
        ['Programming & Data Analysis', 'Python', 5],
        ['Programming & Data Analysis', 'SQL', 5],
        ['Big Data Technologies', 'PySpark', 5],
        ['Big Data Technologies', 'Spark SQL', 4],
        ['Cloud Computing', 'ADF', 5],
        ['Cloud Computing', 'Databricks', 5],
        ['Cloud Computing', 'ADLS', 4],
        ['Cloud Computing', 'Synapse', 4],
        ['Cloud Computing', 'Logic Apps', 4],
        ['Data Engineering', 'ETL/ELT Pipeline', 5]
    ];
    
    $platformSkills = [
        ['Tools & Platforms', 'MS Excel', 4],
        ['Tools & Platforms', 'VSCode', 5],
        ['Tools & Platforms', 'SSMS', 4],
        ['Tools & Platforms', 'Linux', 4],
        ['Tools & Platforms', 'Windows', 4],
        ['Tools & Platforms', 'Azure DevOps', 5],
        ['Tools & Platforms', 'Data Modeling', 4],
        ['Familiar', 'PowerBI', 3],
        ['Familiar', 'Spark Streaming', 3],
        ['Familiar', 'FastAPI', 3],
        ['Familiar', 'GenAI', 3]
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
            'Databricks Certified Data Engineer Associate',
            'Professional certification validating expertise in building and optimizing data engineering solutions with Databricks',
            'Jan 2026'
        ],
        [
            'Microsoft Certified: Fabric Data Engineer Associate',
            'Certification demonstrating proficiency in designing and implementing Microsoft Fabric data engineering solutions',
            'Mar 2026'
        ],
        [
            'Microsoft Certified: Azure Data Fundamentals',
            'Certification validating foundational knowledge of core data concepts and Azure data services',
            'Dec 2025'
        ],
        [
            'National Science Olympiad (2013)',
            'International Rank: 9, State Rank: 3',
            '2013'
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
            'Data Lake Architecture',
            'Designed and implemented a scalable data lake architecture using Azure Data Lake Storage and Databricks Delta Lake, implementing medallion architecture patterns.',
            json_encode(['Azure', 'Databricks', 'Delta Lake']),
            'https://github.com/saurabh22suman/data-lake-architecture',
            ''
        ],
        [
            'ETL Pipeline Framework',
            'Built a reusable ETL framework on PySpark that handles incremental loads, data validation, and error handling with configurable pipeline stages.',
            json_encode(['PySpark', 'Python', 'Data Engineering']),
            'https://github.com/saurabh22suman/etl-pipeline-framework',
            ''
        ],
        [
            'Data Quality Monitor',
            'Created an automated data quality monitoring tool that validates data integrity, completeness, and consistency across various data pipeline stages.',
            json_encode(['Python', 'SQL', 'Data Quality']),
            'https://github.com/saurabh22suman/data-quality-monitor',
            ''
        ],
        [
            'API Data Ingestion',
            'Developed a robust system for incremental API data ingestion with automatic pagination handling and fault tolerance capabilities.',
            json_encode(['Python', 'REST API', 'Azure Logic Apps']),
            'https://github.com/saurabh22suman/api-data-ingestion',
            ''
        ],
        [
            'Spark Performance Optimization',
            'Implemented performance optimization techniques for Spark applications, reducing job execution time by 40% through partition tuning and caching strategies.',
            json_encode(['Spark', 'Performance Tuning', 'Big Data']),
            'https://github.com/saurabh22suman/spark-optimization',
            ''
        ],
        [
            'Portfolio Website',
            'A responsive PHP portfolio/resume website with print functionality. Features modern design with Bootstrap and comprehensive resume sections.',
            json_encode(['CSS', 'PHP', 'Bootstrap']),
            'https://github.com/saurabh22suman/soloengine.in',
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