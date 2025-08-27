-- Migraciones para Sports Service
-- Base de datos: wl_school_sports

USE wl_school_sports;

-- Tabla de deportes
CREATE TABLE sports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('individual', 'team', 'mixed') NOT NULL,
    min_players INT DEFAULT 1,
    max_players INT,
    equipment_required TEXT,
    rules TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
);

-- Tabla de temporadas deportivas
CREATE TABLE seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_start DATE NOT NULL,
    registration_end DATE NOT NULL,
    status ENUM('upcoming', 'registration_open', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Tabla de equipos
CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    category ENUM('junior', 'senior', 'mixed', 'beginner', 'intermediate', 'advanced') NOT NULL,
    grade_levels JSON, -- Array de niveles permitidos
    max_members INT DEFAULT 25,
    current_members INT DEFAULT 0,
    coach_id BIGINT UNSIGNED,
    assistant_coach_id BIGINT UNSIGNED,
    captain_id BIGINT UNSIGNED,
    logo VARCHAR(255),
    uniform_colors VARCHAR(100),
    status ENUM('forming', 'active', 'inactive', 'disbanded') DEFAULT 'forming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    INDEX idx_sport_season (sport_id, season_id),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Tabla de miembros de equipo
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    position VARCHAR(50),
    jersey_number INT,
    join_date DATE NOT NULL,
    leave_date DATE NULL,
    status ENUM('active', 'inactive', 'suspended', 'injured') DEFAULT 'active',
    performance_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_team_student (team_id, student_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_team_jersey (team_id, jersey_number)
);

-- Tabla de entrenadores
CREATE TABLE coaches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    specializations JSON, -- Array de deportes especializados
    certifications TEXT,
    experience_years INT DEFAULT 0,
    hire_date DATE,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Tabla de competencias/torneos
CREATE TABLE competitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    sport_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    type ENUM('league', 'tournament', 'championship', 'friendly') NOT NULL,
    format ENUM('round_robin', 'knockout', 'group_stage', 'swiss') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_deadline DATE NOT NULL,
    max_teams INT,
    entry_fee DECIMAL(10,2) DEFAULT 0,
    prize_description TEXT,
    rules TEXT,
    status ENUM('upcoming', 'registration_open', 'in_progress', 'completed', 'cancelled') DEFAULT 'upcoming',
    organizer VARCHAR(100),
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    INDEX idx_sport_season (sport_id, season_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Tabla de participación en competencias
CREATE TABLE competition_teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    registration_date DATE NOT NULL,
    group_name VARCHAR(10), -- Para competencias con grupos
    seed_position INT, -- Posición inicial
    status ENUM('registered', 'confirmed', 'withdrawn', 'disqualified') DEFAULT 'registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_competition_team (competition_id, team_id),
    INDEX idx_status (status)
);

-- Tabla de partidos/juegos
CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    match_date DATETIME NOT NULL,
    venue VARCHAR(255),
    round VARCHAR(50), -- Ej: 'Cuartos de Final', 'Jornada 5'
    home_score INT DEFAULT 0,
    away_score INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'postponed', 'cancelled') DEFAULT 'scheduled',
    referee VARCHAR(100),
    duration_minutes INT DEFAULT 90,
    notes TEXT,
    weather_conditions VARCHAR(100),
    attendance INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL,
    FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_competition (competition_id),
    INDEX idx_match_date (match_date),
    INDEX idx_status (status),
    INDEX idx_teams (home_team_id, away_team_id)
);

-- Tabla de eventos del partido
CREATE TABLE match_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED,
    event_type ENUM('goal', 'yellow_card', 'red_card', 'substitution', 'injury', 'timeout', 'other') NOT NULL,
    minute INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_match_id (match_id),
    INDEX idx_event_type (event_type)
);

-- Tabla de estadísticas de jugadores
CREATE TABLE player_statistics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    matches_played INT DEFAULT 0,
    matches_started INT DEFAULT 0,
    minutes_played INT DEFAULT 0,
    goals INT DEFAULT 0,
    assists INT DEFAULT 0,
    yellow_cards INT DEFAULT 0,
    red_cards INT DEFAULT 0,
    statistics JSON, -- Estadísticas específicas por deporte
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player_team_season (student_id, team_id, season_id),
    INDEX idx_season (season_id)
);

-- Tabla de entrenamientos
CREATE TABLE training_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    coach_id BIGINT UNSIGNED NOT NULL,
    session_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 90,
    location VARCHAR(255),
    objectives TEXT,
    activities TEXT,
    attendance_count INT DEFAULT 0,
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE,
    INDEX idx_team_date (team_id, session_date),
    INDEX idx_status (status)
);

-- Tabla de asistencia a entrenamientos
CREATE TABLE training_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_session_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    arrival_time TIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (training_session_id) REFERENCES training_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_student (training_session_id, student_id),
    INDEX idx_status (status)
);

-- Insertar deportes por defecto
INSERT INTO sports (name, description, category, min_players, max_players, equipment_required) VALUES
('Fútbol', 'Deporte de equipo jugado con los pies', 'team', 11, 22, 'Balón, arcos, uniformes, canilleras'),
('Baloncesto', 'Deporte de equipo con canasta', 'team', 5, 12, 'Balón, canastas, uniformes'),
('Voleibol', 'Deporte de equipo con red', 'team', 6, 12, 'Balón, red, uniformes'),
('Atletismo', 'Deportes de pista y campo', 'individual', 1, 1, 'Implementos según disciplina'),
('Natación', 'Deporte acuático individual', 'individual', 1, 1, 'Piscina, cronómetros'),
('Tenis de Mesa', 'Deporte de raqueta individual o dobles', 'individual', 1, 2, 'Mesa, raquetas, pelotas'),
('Ajedrez', 'Deporte mental estratégico', 'individual', 1, 1, 'Tablero, piezas, reloj');