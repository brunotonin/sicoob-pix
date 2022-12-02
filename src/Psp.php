<?php

namespace Elemke\SicoobPix;

use Dotenv\Dotenv;

class Psp
{
    private $urlToken;
    private $scope;
    private $baseUrlPix;
    private $certificadoPublico;
    private $certificadoPrivado;
    private $token;
    private $timeToken;

    /**
     * Realiza conexão com o ambiente do Sicoob
     * @param array $scope define qual escopo da conexao
     */
    public function __construct(array $scope)
    {
        $path = './';
        if (strpos(realpath($path), 'public')) {
            $path = "../";
        }
        try {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();

        } catch (\Throwable $th) {
        }
        $this->scope = implode(' ', $scope);
        $this->urlToken = Endpoint::URL_AUTENTICACAO;
        $this->baseUrlPix = Endpoint::URL_PIX;
        $this->certificadoPublico = [realpath(getenv['SICOOBPIX_CAMINHO_CERT_PUBLICO']), getenv['SICOOBPIX_SENHA_CERT_PUBLICO']];
        $this->certificadoPrivado = [realpath(getenv['SICOOBPIX_CAMINHO_CERT_PRIVADO']), getenv['SICOOBPIX_SENHA_CERT_PRIVADO']];
    }


    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function gerarToken(): void
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $this->urlToken, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => getenv['SICOOBPIX_CLIENT_ID'],
                    'client_secret' => getenv['SICOOBPIX_CLIENT_SECRET'],
                    'scope' => $this->scope
                ],
                'cert' => $this->certificadoPublico,
                'ssl_key' => $this->certificadoPrivado
            ]);
            $this->token = $response->getBody()->getContents();
            $this->timeToken = time();
        } catch (\Exception $exc) {
            throw $exc;
        }
    }

    /**
     * Token para requisições ao Sicoob
     * @return string
     */
    public function getToken(): string
    {
        if (is_null($this->token)) {
            $this->gerarToken();
        }
        $token = json_decode($this->token);
        $tokenExpiracao = $this->timeToken + $token->expires_in;
        if ($tokenExpiracao < time()) {
            $this->gerarToken();
        }
        $token = json_decode($this->token);
        return $token->access_token;
    }

    /**
     * Retorna url pix do ambiente informado
     * @return string
     */
    public function getUrlPix(): string
    {
        return $this->baseUrlPix;
    }

    /**
     * Retorna certificado publico
     * @return array
     */
    public function getCertificadoPublico(): array
    {
        return $this->certificadoPublico;
    }

    /**
     * Retorna certificado privado
     * @return array
     */
    public function getCertificadoPrivado(): array
    {
        return $this->certificadoPrivado;
    }


}