
const formularioLogin = document.getElementById("form-login");
const areaLogin = document.getElementById("area-login");
const areaProjetos = document.getElementById("area-projetos");

const mensagemLogin = document.getElementById("login-mensagem");
const mensagemProjetos = document.getElementById("projetos-mensagem");
const listaProjetos = document.getElementById("projetos-lista");
const totalProjetos = document.getElementById("total-projetos");
// Realizar login
formularioLogin.addEventListener("submit", async (evento) => {
    evento.preventDefault();

    const email = document.getElementById("login-email").value.trim();
    const senha = document.getElementById("login-senha").value;

    mensagemLogin.textContent = "Verificando acesso...";
    mensagemLogin.className = "mensagem";

    try {
        const resposta = await fetch("../auth/login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ email, senha })
        });

        const resultado = await resposta.json();

        if (!resposta.ok || !resultado.sucesso) {
            mensagemLogin.textContent = resultado.mensagem;
            mensagemLogin.className = "mensagem erro";
            return;
        }

        mensagemLogin.textContent = resultado.mensagem;
        mensagemLogin.className = "mensagem sucesso";

        areaLogin.hidden = true;
        areaProjetos.hidden = false;

        await carregarProjetos();

    } catch (erro) {
        console.error("Erro no login:", erro);
        mensagemLogin.textContent = "Não foi possível conectar ao servidor.";
        mensagemLogin.className = "mensagem erro";
    }
});

// Carregar projetos
async function carregarProjetos() {
    mensagemProjetos.textContent = "Carregando projetos...";
    listaProjetos.replaceChildren();

    try {
        const resposta = await fetch("../projects/index.php");
        const resultado = await resposta.json();

        if (!resposta.ok || !resultado.sucesso) {
            mensagemProjetos.textContent = resultado.mensagem;
            mensagemProjetos.className = "mensagem erro";
            return;
        }

        const projetos = resultado.dados;
        totalProjetos.textContent = Array.isArray(projetos) ? projetos.length : 0;

        if (!Array.isArray(projetos) || projetos.length === 0) {
            mensagemProjetos.textContent = "Nenhum projeto encontrado.";
            mensagemProjetos.className = "mensagem";
            return;
        }

        projetos.forEach((projeto) => {
            const item = document.createElement("li");

            const nome = document.createElement("h3");
            nome.textContent = projeto.nome;

            const descricao = document.createElement("p");
            descricao.textContent = projeto.descricao || "Sem descrição.";

            item.appendChild(nome);
            item.appendChild(descricao);

            listaProjetos.appendChild(item);
        });

        mensagemProjetos.textContent = "Projetos carregados com sucesso.";
        mensagemProjetos.className = "mensagem sucesso";

    } catch (erro) {
        console.error("Erro ao carregar projetos:", erro);
        mensagemProjetos.textContent = "Não foi possível carregar os projetos.";
        mensagemProjetos.className = "mensagem erro";
    }
}
