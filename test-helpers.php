<?php

/*
|--------------------------------------------------------------------------
| Test Helper Functions
|--------------------------------------------------------------------------
|
| Este arquivo testa as funções helpers criadas para geração de dados
| realísticos brasileiros.
|
| Para executar: php test-helpers.php
|
*/

// Carrega o autoload do Composer
require __DIR__ . '/vendor/autoload.php';

echo "\n🧪 Testando Helpers de Geração de Dados\n";
echo "========================================\n\n";

// Teste 1: Gerar CPF
echo "📋 Teste 1: Gerar CPF Válido\n";
for ($i = 0; $i < 5; $i++) {
    $cpf = generate_cpf();
    $num = $i + 1;
    echo "   CPF {$num}: {$cpf}\n";
}

// Teste 2: Gerar RG
echo "\n📋 Teste 2: Gerar RG\n";
for ($i = 0; $i < 5; $i++) {
    $rg = generate_rg();
    $num = $i + 1;
    echo "   RG {$num}: {$rg}\n";
}

// Teste 3: Gerar Telefones
echo "\n📞 Teste 3: Gerar Telefones\n";
echo "   Fixos:\n";
for ($i = 0; $i < 3; $i++) {
    $phone = brazilian_phone(false);
    echo "      {$phone}\n";
}
echo "   Celulares:\n";
for ($i = 0; $i < 3; $i++) {
    $phone = brazilian_phone(true);
    echo "      {$phone}\n";
}

// Teste 4: Nomes Brasileiros
echo "\n👤 Teste 4: Nomes Brasileiros\n";
$names = brazilian_names();
echo "   Masculinos (5 exemplos):\n";
for ($i = 0; $i < 5; $i++) {
    echo "      " . $names['male'][$i] . "\n";
}
echo "   Femininos (5 exemplos):\n";
for ($i = 0; $i < 5; $i++) {
    echo "      " . $names['female'][$i] . "\n";
}

// Teste 5: Cidades Brasileiras
echo "\n🏙️  Teste 5: Cidades Brasileiras\n";
$cities = brazilian_cities();
foreach (array_slice(array_keys($cities), 0, 5) as $state) {
    echo "   {$state}: ";
    echo implode(', ', array_slice($cities[$state], 0, 3)) . "...\n";
}

// Teste 6: Domínios de Email
echo "\n📧 Teste 6: Domínios de Email\n";
$domains = email_domains();
foreach ($domains as $domain) {
    echo "   @{$domain}\n";
}

// Teste 7: Emails Gerados
echo "\n📬 Teste 7: Emails Gerados (simulação)\n";
$names = brazilian_names();
for ($i = 0; $i < 5; $i++) {
    $name = $names['male'][array_rand($names['male'])];
    $emailUser = strtolower(str_replace(' ', '.', $name));
    // Remove acentos
    $unwanted = ['á' => 'a', 'ã' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c'];
    $emailUser = strtr($emailUser, $unwanted);
    $domain = $domains[array_rand($domains)];
    $email = $emailUser . rand(1, 999) . '@' . $domain;
    echo "   {$name} -> {$email}\n";
}

// Teste 8: Endereços
echo "\n🏠 Teste 8: Ruas e Bairros\n";
$streets = brazilian_streets();
$districts = brazilian_districts();
for ($i = 0; $i < 5; $i++) {
    $street = $streets[array_rand($streets)];
    $district = $districts[array_rand($districts)];
    $number = rand(10, 9999);
    echo "   {$street}, {$number} - {$district}\n";
}

// Teste 9: Qualificações de Professores
echo "\n🎓 Teste 9: Qualificações de Professores\n";
$qualifications = teacher_qualifications();
foreach (array_slice($qualifications, 0, 5) as $qual) {
    echo "   {$qual}\n";
}

echo "\n✅ Todos os testes concluídos com sucesso!\n\n";
