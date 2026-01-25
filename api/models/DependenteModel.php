<?php
/**
 * =====================================================
 * MODEL: DependenteModel
 * =====================================================
 * 
 * Responsável por todas as operações de banco de dados
 * relacionadas a dependentes
 * 
 * @author Sistema ERP Serra da Liberdade
 * @date 25/01/2026
 * @version 1.0
 */

class DependenteModel {
    
    private $conexao;
    private $tabela = 'dependentes';
    
    /**
     * Construtor
     * @param object $conexao Conexão com banco de dados
     */
    public function __construct($conexao) {
        $this->conexao = $conexao;
    }
    
    /**
     * Listar todos os dependentes de um morador
     * @param int $morador_id ID do morador
     * @param bool $apenas_ativos Se true, retorna apenas dependentes ativos
     * @return array Array com dependentes
     */
    public function listarPorMorador($morador_id, $apenas_ativos = true) {
        $sql = "SELECT * FROM {$this->tabela} WHERE morador_id = ?";
        
        if ($apenas_ativos) {
            $sql .= " AND ativo = 1";
        }
        
        $sql .= " ORDER BY nome_completo ASC";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("i", $morador_id);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $dependentes = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $dependentes[] = $row;
        }
        
        $stmt->close();
        return $dependentes;
    }
    
    /**
     * Obter dependente por ID
     * @param int $id ID do dependente
     * @return array|null Dados do dependente ou null
     */
    public function obterPorId($id) {
        $sql = "SELECT * FROM {$this->tabela} WHERE id = ?";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $dependente = $resultado->fetch_assoc();
        
        $stmt->close();
        return $dependente;
    }
    
    /**
     * Obter dependente por CPF
     * @param string $cpf CPF do dependente
     * @return array|null Dados do dependente ou null
     */
    public function obterPorCpf($cpf) {
        $sql = "SELECT * FROM {$this->tabela} WHERE cpf = ?";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $dependente = $resultado->fetch_assoc();
        
        $stmt->close();
        return $dependente;
    }
    
    /**
     * Verificar se CPF já existe como dependente
     * @param string $cpf CPF a verificar
     * @param int $morador_id ID do morador
     * @param int $exclude_id ID a excluir da busca (para edição)
     * @return bool True se existe
     */
    public function cpfExiste($cpf, $morador_id = null, $exclude_id = null) {
        $sql = "SELECT id FROM {$this->tabela} WHERE cpf = ?";
        
        if ($morador_id) {
            $sql .= " AND morador_id = ?";
        }
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $this->conexao->prepare($sql);
        
        if ($morador_id && $exclude_id) {
            $stmt->bind_param("sii", $cpf, $morador_id, $exclude_id);
        } elseif ($morador_id) {
            $stmt->bind_param("si", $cpf, $morador_id);
        } elseif ($exclude_id) {
            $stmt->bind_param("si", $cpf, $exclude_id);
        } else {
            $stmt->bind_param("s", $cpf);
        }
        
        $stmt->execute();
        $resultado = $stmt->get_result();
        $existe = $resultado->num_rows > 0;
        
        $stmt->close();
        return $existe;
    }
    
    /**
     * Criar novo dependente
     * @param array $dados Dados do dependente
     * @return array ['sucesso' => bool, 'id' => int, 'mensagem' => string]
     */
    public function criar($dados) {
        // Validações básicas
        if (empty($dados['morador_id']) || empty($dados['nome_completo']) || empty($dados['cpf'])) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados obrigatórios faltando'
            ];
        }
        
        // Verificar se CPF já existe
        if ($this->cpfExiste($dados['cpf'])) {
            return [
                'sucesso' => false,
                'mensagem' => 'CPF já cadastrado no sistema'
            ];
        }
        
        // Preparar dados
        $morador_id = (int)$dados['morador_id'];
        $nome_completo = trim($dados['nome_completo']);
        $cpf = trim($dados['cpf']);
        $email = isset($dados['email']) ? trim($dados['email']) : null;
        $telefone = isset($dados['telefone']) ? trim($dados['telefone']) : null;
        $celular = isset($dados['celular']) ? trim($dados['celular']) : null;
        $data_nascimento = isset($dados['data_nascimento']) ? $dados['data_nascimento'] : null;
        $parentesco = isset($dados['parentesco']) ? trim($dados['parentesco']) : 'Outro';
        $observacao = isset($dados['observacao']) ? trim($dados['observacao']) : null;
        
        $sql = "INSERT INTO {$this->tabela} 
                (morador_id, nome_completo, cpf, email, telefone, celular, data_nascimento, parentesco, observacao, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->conexao->prepare($sql);
        
        if (!$stmt) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao preparar statement: ' . $this->conexao->error
            ];
        }
        
        $stmt->bind_param(
            "isssssss",
            $morador_id,
            $nome_completo,
            $cpf,
            $email,
            $telefone,
            $celular,
            $data_nascimento,
            $parentesco,
            $observacao
        );
        
        if ($stmt->execute()) {
            $id = $this->conexao->insert_id;
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            // Verificar se realmente foi inserido
            if ($id > 0 && $affected_rows > 0) {
                // Verificar se o registro existe no banco
                $verificacao = $this->obterPorId($id);
                
                if ($verificacao) {
                    return [
                        'sucesso' => true,
                        'id' => $id,
                        'mensagem' => 'Dependente cadastrado com sucesso',
                        'dados' => $verificacao
                    ];
                } else {
                    return [
                        'sucesso' => false,
                        'mensagem' => 'Erro: Dependente não foi salvo no banco de dados. Verifique os logs do sistema.',
                        'debug' => [
                            'insert_id' => $id,
                            'affected_rows' => $affected_rows,
                            'verificacao' => 'falhou'
                        ]
                    ];
                }
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro: Dependente não foi cadastrado. Nenhuma linha foi afetada no banco de dados.',
                    'debug' => [
                        'insert_id' => $id,
                        'affected_rows' => $affected_rows
                    ]
                ];
            }
        } else {
            $erro = $stmt->error;
            $errno = $stmt->errno;
            $stmt->close();
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao cadastrar dependente no banco de dados',
                'erro_detalhado' => $erro,
                'debug' => [
                    'errno' => $errno,
                    'error' => $erro
                ]
            ];
        }
    }
    
    /**
     * Atualizar dependente
     * @param int $id ID do dependente
     * @param array $dados Dados a atualizar
     * @return array ['sucesso' => bool, 'mensagem' => string]
     */
    public function atualizar($id, $dados) {
        $id = (int)$id;
        
        // Verificar se dependente existe
        $dependente = $this->obterPorId($id);
        if (!$dependente) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dependente não encontrado'
            ];
        }
        
        // Verificar CPF duplicado
        if (isset($dados['cpf']) && $dados['cpf'] != $dependente['cpf']) {
            if ($this->cpfExiste($dados['cpf'], null, $id)) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'CPF já cadastrado no sistema'
                ];
            }
        }
        
        // Preparar dados
        $campos = [];
        $tipos = "";
        $valores = [];
        
        if (isset($dados['nome_completo'])) {
            $campos[] = "nome_completo = ?";
            $tipos .= "s";
            $valores[] = trim($dados['nome_completo']);
        }
        
        if (isset($dados['cpf'])) {
            $campos[] = "cpf = ?";
            $tipos .= "s";
            $valores[] = trim($dados['cpf']);
        }
        
        if (isset($dados['email'])) {
            $campos[] = "email = ?";
            $tipos .= "s";
            $valores[] = trim($dados['email']);
        }
        
        if (isset($dados['telefone'])) {
            $campos[] = "telefone = ?";
            $tipos .= "s";
            $valores[] = trim($dados['telefone']);
        }
        
        if (isset($dados['celular'])) {
            $campos[] = "celular = ?";
            $tipos .= "s";
            $valores[] = trim($dados['celular']);
        }
        
        if (isset($dados['data_nascimento'])) {
            $campos[] = "data_nascimento = ?";
            $tipos .= "s";
            $valores[] = $dados['data_nascimento'];
        }
        
        if (isset($dados['parentesco'])) {
            $campos[] = "parentesco = ?";
            $tipos .= "s";
            $valores[] = trim($dados['parentesco']);
        }
        
        if (isset($dados['observacao'])) {
            $campos[] = "observacao = ?";
            $tipos .= "s";
            $valores[] = trim($dados['observacao']);
        }
        
        if (empty($campos)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Nenhum dado para atualizar'
            ];
        }
        
        $sql = "UPDATE {$this->tabela} SET " . implode(", ", $campos) . " WHERE id = ?";
        
        $stmt = $this->conexao->prepare($sql);
        
        if (!$stmt) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao preparar statement: ' . $this->conexao->error
            ];
        }
        
        $tipos .= "i";
        $valores[] = $id;
        
        $stmt->bind_param($tipos, ...$valores);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Dependente atualizado com sucesso'
            ];
        } else {
            $erro = $stmt->error;
            $stmt->close();
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar dependente: ' . $erro
            ];
        }
    }
    
    /**
     * Ativar/Inativar dependente
     * @param int $id ID do dependente
     * @param bool $ativo Status desejado
     * @return array ['sucesso' => bool, 'mensagem' => string]
     */
    public function ativarInativar($id, $ativo) {
        $id = (int)$id;
        $ativo = $ativo ? 1 : 0;
        
        $sql = "UPDATE {$this->tabela} SET ativo = ? WHERE id = ?";
        
        $stmt = $this->conexao->prepare($sql);
        
        if (!$stmt) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao preparar statement: ' . $this->conexao->error
            ];
        }
        
        $stmt->bind_param("ii", $ativo, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            $acao = $ativo ? 'ativado' : 'inativado';
            return [
                'sucesso' => true,
                'mensagem' => "Dependente {$acao} com sucesso"
            ];
        } else {
            $erro = $stmt->error;
            $stmt->close();
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar dependente: ' . $erro
            ];
        }
    }
    
    /**
     * Deletar dependente
     * @param int $id ID do dependente
     * @return array ['sucesso' => bool, 'mensagem' => string]
     */
    public function deletar($id) {
        $id = (int)$id;
        
        // Verificar se dependente existe
        $dependente = $this->obterPorId($id);
        if (!$dependente) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dependente não encontrado'
            ];
        }
        
        $sql = "DELETE FROM {$this->tabela} WHERE id = ?";
        
        $stmt = $this->conexao->prepare($sql);
        
        if (!$stmt) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao preparar statement: ' . $this->conexao->error
            ];
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Dependente deletado com sucesso'
            ];
        } else {
            $erro = $stmt->error;
            $stmt->close();
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao deletar dependente: ' . $erro
            ];
        }
    }
    
    /**
     * Obter total de dependentes de um morador
     * @param int $morador_id ID do morador
     * @return int Total de dependentes
     */
    public function totalPorMorador($morador_id) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabela} WHERE morador_id = ? AND ativo = 1";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("i", $morador_id);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();
        
        $stmt->close();
        return (int)$row['total'];
    }
    
    /**
     * Listar dependentes com informações de veículos
     * @param int $morador_id ID do morador
     * @return array Array com dependentes e seus veículos
     */
    public function listarComVeiculos($morador_id) {
        $sql = "SELECT 
                    d.id,
                    d.nome_completo,
                    d.cpf,
                    d.parentesco,
                    d.ativo,
                    COUNT(v.id) as total_veiculos,
                    GROUP_CONCAT(v.placa, ', ') as placas_veiculos
                FROM {$this->tabela} d
                LEFT JOIN veiculos v ON d.id = v.dependente_id AND v.ativo = 1
                WHERE d.morador_id = ?
                GROUP BY d.id
                ORDER BY d.nome_completo ASC";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("i", $morador_id);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        $dependentes = [];
        
        while ($row = $resultado->fetch_assoc()) {
            $dependentes[] = $row;
        }
        
        $stmt->close();
        return $dependentes;
    }
}
?>
