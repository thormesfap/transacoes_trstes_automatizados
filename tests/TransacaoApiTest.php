<?php

use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\HeadersFactory;
use Slim\Factory\AppFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TransacaoApiTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = AppFactory::create();
        (require __DIR__ . '/../routes/web.php')($this->app);
        
        //Apaga todas as transações do banco entre os testes
        $delete = (new ServerRequestFactory())->createServerRequest('DELETE', '/transacao');
        $this->app->handle($delete);
    }

    #[Test]
    public function consegue_criar_transacao_valida()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'valor' => 100.5,
            'dataHora' => date('c', strtotime('-10 seconds'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao')->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        
        $response = $this->app->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function nao_consegue_criar_transacao_com_id_em_duplicidade()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'valor' => 50,
            'dataHora' => date('c', strtotime('-20 seconds'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $response = $this->app->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        $response = $this->app->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
    }
    
    #[Test]
    public function nao_consegue_criar_transacao_com_data_futura()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174002',
            'valor' => 10,
            'dataHora' => date('c', strtotime('+1 day'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $response = $this->app->handle($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    
    #[Test]
    public function consegue_buscar_transacao_existente()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174003',
            'valor' => 100,
            'dataHora' => date('c', strtotime('-1 day'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $response = $this->app->handle($request);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/transacao/' . $data['id']);
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        $this->assertEquals($data['id'], $json['id']);
    }
    
    #[Test]
    public function nao_consegue_buscar_transacao_inexistente()
    {
        
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/transacao/' . 'inexistente');
        $response = $this->app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function testDeleteTodasTransacoes()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174004',
            'valor' => 30,
            'dataHora' => date('c', strtotime('-40 seconds'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $response = $this->app->handle($request);
        $delete = (new ServerRequestFactory())->createServerRequest('DELETE', '/transacao');
        $response = $this->app->handle($delete);
        $this->assertEquals(200, $response->getStatusCode());
        $search = (new ServerRequestFactory())->createServerRequest('GET', '/transacao/' . $data['id']);
        $response = $this->app->handle($search);
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function consegue_apagar_transacao_existente()
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174005',
            'valor' => 40,
            'dataHora' => date('c', strtotime('-50 seconds'))
        ];
        $create = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $create->getBody()->write(json_encode($data));
        $create->getBody()->rewind();
        $response = $this->app->handle($create);
        $delete = (new ServerRequestFactory())->createServerRequest('DELETE', '/transacao/' . $data['id']);
        $response = $this->app->handle($delete);
        $this->assertEquals(200, $response->getStatusCode());
        $search = (new ServerRequestFactory())->createServerRequest('GET', '/transacao/' . $data['id']);
        $response = $this->app->handle($search);
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function consegue_buscar_estatisticas_recentes()
    {
        $data1 = 
        [
            'id' => '123e4567-e89b-12d3-a456-426614174006',
            'valor' => 10,
            'dataHora' => date('c', strtotime('-20 seconds'))
        ];
        $data2 = [
            'id' => '123e4567-e89b-12d3-a456-426614174007',
            'valor' => 20,
            'dataHora' => date('c', strtotime('-10 seconds'))
        ];
        $request1 = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request1->getBody()->write(json_encode($data1));
        $request1->getBody()->rewind();
        $this->app->handle($request1);

        $request2 = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request2->getBody()->write(json_encode($data2));
        $request2->getBody()->rewind();
        $this->app->handle($request2);
        $stats = (new ServerRequestFactory())->createServerRequest('GET', '/estatistica');
        $response = $this->app->handle($stats);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        $this->assertEquals(2, $json['count']);
        $this->assertEquals(30, $json['sum']);
        $this->assertEquals(15, $json['avg']);
        $this->assertEquals(10, $json['min']);
        $this->assertEquals(20, $json['max']);
    }
    
    #[Test]
    public function testGetEstatisticaSemTransacoesRecentes()
    {
        $stats = (new ServerRequestFactory())->createServerRequest('GET', '/estatistica');
        $response = $this->app->handle($stats);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        $this->assertEquals(0, $json['count']);
        $this->assertEquals(0, $json['sum']);
        $this->assertEquals(0, $json['avg']);
        $this->assertEquals(0, $json['min']);
        $this->assertEquals(0, $json['max']);
    }

    #[Test]
    public function testGetEstatisticaComTransacaoForaDoIntervalo()
    {
        $data =  [
            'id' => '123e4567-e89b-12d3-a456-426614174008',
            'valor' => 99,
            'dataHora' => date('c', strtotime('-120 seconds'))
        ];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/transacao');
        $request->getBody()->write(json_encode($data));
        $request->getBody()->rewind();
        $this->app->handle($request);
        $stats = (new ServerRequestFactory())->createServerRequest('GET', '/estatistica');
        $response = $this->app->handle($stats);
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody(), true);
        $this->assertEquals(0, $json['count']);
    }
}
