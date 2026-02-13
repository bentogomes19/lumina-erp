<?php

if (! function_exists('generate_cpf')) {
    /**
     * Gera um CPF válido aleatório
     */
    function generate_cpf(): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = rand(0, 9);

        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) {
            $d1 = 0;
        }

        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) {
            $d2 = 0;
        }

        return sprintf('%d%d%d.%d%d%d.%d%d%d-%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $d1, $d2);
    }
}

if (! function_exists('generate_rg')) {
    /**
     * Gera um RG válido aleatório (formato SP)
     */
    function generate_rg(): string
    {
        $n1 = rand(10, 99);
        $n2 = rand(100, 999);
        $n3 = rand(100, 999);
        $dv = rand(0, 9);

        return sprintf('%d.%d.%d-%d', $n1, $n2, $n3, $dv);
    }
}

if (! function_exists('brazilian_phone')) {
    /**
     * Gera um telefone brasileiro válido
     */
    function brazilian_phone(bool $mobile = false): string
    {
        $ddd = rand(11, 99); // DDD brasileiro
        
        if ($mobile) {
            // Celular (9 dígitos)
            $numero = '9' . rand(1000, 9999) . '-' . rand(1000, 9999);
        } else {
            // Fixo (8 dígitos)
            $numero = rand(2000, 5999) . '-' . rand(1000, 9999);
        }

        return "({$ddd}) {$numero}";
    }
}

if (! function_exists('brazilian_names')) {
    /**
     * Retorna lista de nomes brasileiros comuns
     */
    function brazilian_names(): array
    {
        return [
            'male' => [
                'João Silva', 'Pedro Santos', 'Lucas Oliveira', 'Gabriel Souza', 'Rafael Costa',
                'Matheus Alves', 'Felipe Rodrigues', 'Bruno Pereira', 'Guilherme Lima', 'Thiago Ferreira',
                'Vinicius Martins', 'Leonardo Ribeiro', 'Diego Carvalho', 'Rodrigo Almeida', 'Fernando Dias',
                'Gustavo Cardoso', 'Henrique Gomes', 'Arthur Mendes', 'Eduardo Barbosa', 'Marcelo Ramos',
                'André Araújo', 'Carlos Nascimento', 'Daniel Castro', 'Fábio Lopes', 'Ricardo Monteiro',
                'Roberto Correia', 'Paulo Pinto', 'José Moreira', 'Antonio Amaral', 'Marcos Teixeira',
                'Caio Freitas', 'Igor Nunes', 'Samuel Vieira', 'Enzo Rocha', 'Miguel Cavalcanti',
                'Bernardo Duarte', 'Davi Campos', 'Heitor Moura', 'Nicolas Barros', 'Cauã Xavier',
            ],
            'female' => [
                'Maria Silva', 'Ana Santos', 'Julia Oliveira', 'Laura Souza', 'Beatriz Costa',
                'Camila Alves', 'Larissa Rodrigues', 'Fernanda Pereira', 'Juliana Lima', 'Amanda Ferreira',
                'Isabela Martins', 'Letícia Ribeiro', 'Mariana Carvalho', 'Carolina Almeida', 'Gabriela Dias',
                'Bianca Cardoso', 'Aline Gomes', 'Natália Mendes', 'Rafaela Barbosa', 'Vanessa Ramos',
                'Patrícia Araújo', 'Renata Nascimento', 'Tatiana Castro', 'Priscila Lopes', 'Adriana Monteiro',
                'Jéssica Correia', 'Bruna Pinto', 'Carla Moreira', 'Danielle Amaral', 'Elaine Teixeira',
                'Sofia Freitas', 'Alice Nunes', 'Helena Vieira', 'Valentina Rocha', 'Manuela Cavalcanti',
                'Luiza Duarte', 'Giovanna Campos', 'Marina Moura', 'Melissa Barros', 'Lívia Xavier',
            ],
        ];
    }
}

if (! function_exists('brazilian_cities')) {
    /**
     * Retorna lista de cidades brasileiras por estado
     */
    function brazilian_cities(): array
    {
        return [
            'SP' => ['São Paulo', 'Campinas', 'Santos', 'São José dos Campos', 'Ribeirão Preto', 'Sorocaba', 'Osasco', 'Guarulhos', 'Santo André', 'Bauru'],
            'RJ' => ['Rio de Janeiro', 'Niterói', 'Duque de Caxias', 'Nova Iguaçu', 'São Gonçalo', 'Campos dos Goytacazes', 'Petrópolis', 'Volta Redonda'],
            'MG' => ['Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros', 'Ribeirão das Neves', 'Uberaba', 'Governador Valadares'],
            'RS' => ['Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas', 'Santa Maria', 'Gravataí', 'Viamão', 'Novo Hamburgo', 'São Leopoldo'],
            'BA' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari', 'Itabuna', 'Juazeiro', 'Lauro de Freitas', 'Ilhéus'],
            'PR' => ['Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel', 'São José dos Pinhais', 'Foz do Iguaçu', 'Colombo'],
            'PE' => ['Recife', 'Jaboatão dos Guararapes', 'Olinda', 'Paulista', 'Caruaru', 'Petrolina', 'Cabo de Santo Agostinho'],
            'CE' => ['Fortaleza', 'Caucaia', 'Juazeiro do Norte', 'Maracanaú', 'Sobral', 'Crato', 'Itapipoca'],
            'SC' => ['Florianópolis', 'Joinville', 'Blumenau', 'São José', 'Criciúma', 'Chapecó', 'Itajaí', 'Jaraguá do Sul'],
            'GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis', 'Rio Verde', 'Luziânia', 'Águas Lindas de Goiás'],
        ];
    }
}

if (! function_exists('brazilian_streets')) {
    /**
     * Retorna lista de nomes de ruas/avenidas brasileiras comuns
     */
    function brazilian_streets(): array
    {
        return [
            'Rua das Flores', 'Avenida Brasil', 'Rua São Paulo', 'Avenida Paulista',
            'Rua das Acácias', 'Avenida Presidente Vargas', 'Rua Santos Dumont',
            'Avenida Getúlio Vargas', 'Rua 15 de Novembro', 'Avenida Tiradentes',
            'Rua Barão do Rio Branco', 'Avenida Rio Branco', 'Rua Marechal Deodoro',
            'Rua Dom Pedro II', 'Avenida Independência', 'Rua 7 de Setembro',
            'Avenida Beira Mar', 'Rua das Palmeiras', 'Avenida Atlântica',
            'Rua Padre Anchieta', 'Avenida Washington Luís', 'Rua José Bonifácio',
            'Rua Visconde de Mauá', 'Avenida Nove de Julho', 'Rua General Osório',
        ];
    }
}

if (! function_exists('brazilian_districts')) {
    /**
     * Retorna lista de bairros brasileiros comuns
     */
    function brazilian_districts(): array
    {
        return [
            'Centro', 'Jardim Paulista', 'Vila Mariana', 'Moema', 'Perdizes',
            'Pinheiros', 'Consolação', 'Copacabana', 'Ipanema', 'Leblon',
            'Botafogo', 'Tijuca', 'Lapa', 'Brooklin', 'Morumbi', 'Tatuapé',
            'Santana', 'Penha', 'Vila Madalena', 'Alto de Pinheiros', 'Saúde',
            'Vila Olímpia', 'Itaim Bibi', 'Jardim América', 'Bela Vista',
        ];
    }
}

if (! function_exists('email_domains')) {
    /**
     * Retorna lista de domínios de email populares
     */
    function email_domains(): array
    {
        return [
            'gmail.com',
            'hotmail.com',
            'outlook.com',
            'yahoo.com.br',
            'yahoo.com',
            'live.com',
            'icloud.com',
            'uol.com.br',
            'bol.com.br',
            'terra.com.br',
        ];
    }
}

if (! function_exists('teacher_qualifications')) {
    /**
     * Retorna lista de qualificações para professores
     */
    function teacher_qualifications(): array
    {
        return [
            'Licenciatura em Matemática',
            'Licenciatura em Língua Portuguesa',
            'Licenciatura em História',
            'Licenciatura em Geografia',
            'Licenciatura em Biologia',
            'Licenciatura em Química',
            'Licenciatura em Física',
            'Licenciatura em Inglês',
            'Licenciatura em Educação Física',
            'Licenciatura em Artes',
            'Licenciatura em Pedagogia',
            'Licenciatura em Filosofia',
            'Licenciatura em Sociologia',
            'Licenciatura em Ciências',
            'Bacharelado em Letras',
        ];
    }
}
