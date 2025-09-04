# Serviço Windows - Prato Rápido

Este documento explica como configurar o sistema Prato Rápido para iniciar automaticamente como um serviço do Windows.

## 📋 Pré-requisitos

- Windows 10/11 ou Windows Server
- PHP instalado e configurado no PATH do sistema
- Permissões de administrador

## 🚀 Instalação do Serviço

### Passo 1: Executar como Administrador
1. Clique com o botão direito no **PowerShell**
2. Selecione **"Executar como administrador"**

### Passo 2: Navegar até o diretório do projeto
```powershell
cd "C:\Users\faiso\Downloads\CodeIgniter"
```

### Passo 3: Executar o script de instalação
```powershell
.\setup_windows_service.ps1
```

## ✅ Verificação

Após a instalação, o serviço será criado com o nome **"PratoRapidoServer"** e iniciado automaticamente.

### Verificar status do serviço:
```powershell
Get-Service -Name PratoRapidoServer
```

### Acessar o sistema:
Abra seu navegador e acesse: **http://localhost:8080**

## 🔧 Gerenciamento do Serviço

### Parar o serviço:
```powershell
Stop-Service -Name PratoRapidoServer
```

### Iniciar o serviço:
```powershell
Start-Service -Name PratoRapidoServer
```

### Reiniciar o serviço:
```powershell
Restart-Service -Name PratoRapidoServer
```

### Ver logs do serviço:
1. Abra o **Visualizador de Eventos** do Windows
2. Navegue até **Logs do Windows > Sistema**
3. Procure por eventos relacionados ao "PratoRapidoServer"

## 🗑️ Remoção do Serviço

Para remover completamente o serviço:

1. Execute o PowerShell como administrador
2. Navegue até o diretório do projeto
3. Execute o script de remoção:

```powershell
.\remove_windows_service.ps1
```

## 🔍 Solução de Problemas

### Problema: "PHP não encontrado"
**Solução:** Certifique-se de que o PHP está instalado e adicionado ao PATH do sistema.

### Problema: "Acesso negado"
**Solução:** Execute o PowerShell como administrador.

### Problema: "Serviço não inicia"
**Soluções:**
1. Verifique se a porta 8080 não está sendo usada por outro programa
2. Verifique os logs do sistema no Visualizador de Eventos
3. Certifique-se de que todos os arquivos do projeto estão presentes

### Problema: "Erro de permissão de execução"
**Solução:** Execute o seguinte comando no PowerShell como administrador:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

## 📝 Notas Importantes

- O serviço será configurado para iniciar automaticamente com o Windows
- Em caso de falha, o serviço tentará reiniciar automaticamente
- O servidor ficará disponível na porta 8080
- Certifique-se de que o firewall do Windows permite conexões na porta 8080

## 🔒 Configuração de Firewall (Opcional)

Se você quiser acessar o sistema de outros computadores na rede:

```powershell
# Permitir conexões na porta 8080
New-NetFirewallRule -DisplayName "Prato Rápido Server" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow
```

## 📞 Suporte

Em caso de problemas:
1. Verifique os logs do sistema
2. Certifique-se de que todos os pré-requisitos foram atendidos
3. Execute os scripts de remoção e instalação novamente

---

**Desenvolvido para o Sistema Prato Rápido** 🍽️