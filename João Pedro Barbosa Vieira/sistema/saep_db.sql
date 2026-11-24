CREATE DATABASE IF NOT EXISTS clinica_veterinaria
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica_veterinaria;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS agendamentos;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS tutores;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    id_tutor INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tutores (
    id_tutor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    cpf_criptografado TEXT NULL,
    telefone VARCHAR(25) NOT NULL,
    email VARCHAR(150) NULL,
    endereco VARCHAR(255) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuario_tutor FOREIGN KEY (id_tutor)
    REFERENCES tutores(id_tutor) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE pets (
    id_pet INT AUTO_INCREMENT PRIMARY KEY,
    id_tutor INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(50) NOT NULL,
    raca VARCHAR(80) NULL,
    idade INT NULL,
    observacoes TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pet_tutor FOREIGN KEY (id_tutor)
      REFERENCES tutores(id_tutor) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE agendamentos (
    id_agendamento INT AUTO_INCREMENT PRIMARY KEY,
    id_pet INT NOT NULL,
    data_agendamento DATE NOT NULL,
    horario TIME NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    status ENUM('Solicitado','Agendado','Confirmado','Concluído','Cancelado') NOT NULL DEFAULT 'Solicitado',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamento_pet FOREIGN KEY (id_pet)
      REFERENCES pets(id_pet) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


INSERT INTO tutores (nome, cpf_criptografado, telefone, email, endereco) VALUES
('Mariana Alves', NULL, '(81) 99911-2233', 'mariana@email.com', 'Camaragibe - PE'),
('Roberto Santos', NULL, '(81) 98822-3344', 'roberto@email.com', 'Recife - PE'),
('Juliana Costa', NULL, '(81) 97733-4455', 'juliana@email.com', 'São Lourenço da Mata - PE');



INSERT INTO usuarios (nome, email, senha, tipo, id_tutor) VALUES
('Administrador', 'admin@clinica.com', '$2y$12$2Y5JTrAKG8TKddHbly6sm.ukEX76mo/jDdVVdU9AEY/2/FDXCv7x2', 'admin', NULL),
('Ana Souza', 'ana@clinica.com', '$2y$12$2Y5JTrAKG8TKddHbly6sm.ukEX76mo/jDdVVdU9AEY/2/FDXCv7x2', 'usuario', 1),
('Carlos Lima', 'carlos@clinica.com', '$2y$12$2Y5JTrAKG8TKddHbly6sm.ukEX76mo/jDdVVdU9AEY/2/FDXCv7x2', 'usuario', 2);

INSERT INTO pets (id_tutor, nome, especie, raca, idade, observacoes) VALUES
(1, 'Mel', 'Cachorro', 'Labrador', 4, 'Acompanhamento anual'),
(2, 'Mimi', 'Gato', 'Siamês', 2, 'Carteira de vacinação'),
(3, 'Thor', 'Cachorro', 'Vira-lata', 6, 'Consulta de rotina');

INSERT INTO agendamentos (id_pet, data_agendamento, horario, motivo, status) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'Consulta de rotina', 'Agendado'),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:30:00', 'Avaliação geral', 'Confirmado'),
(3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'Retorno veterinário', 'Agendado');
