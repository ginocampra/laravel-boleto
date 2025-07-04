<?php

namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Util;
use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;

class Fibra extends AbstractBoleto implements BoletoContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio('range', 'codigoCliente');
    }

    /**
     * Moeda
     *
     * @var int
     */
    protected $moeda = 0;

    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_FIBRA;

    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = ['1', '5', 'D'];

    /**
     * Espécie do documento, código para remessa do CNAB240
     * @var string
     */
    protected $especiesCodigo = [
        'DM'  => '01', //Duplicata Mercantil
        'NP'  => '02', //Nota Promissória
        'CH'  => '03', //Cheque
        'LC'  => '04', //Letra de Câmbio
        'RC'  => '05', //Recibo
        'AP'  => '08', //Apólice de Seguro
        'DS'  => '12', //Duplicata de Serviço
        'CAR' => '31', //Cartão de crédito
        'O'   => '99',  //Outros,
    ];

    /**
     * Linha de local de pagamento
     *
     * @var string
     */
    protected $localPagamento = 'Canais eletrônicos, agências ou correspondentes bancários de todo o BRASIL';

    /**
     * Código de range de composição do nosso numero.
     *
     * @var int
     */
    protected $range = 0;

    /**
     * @var string
     */
    protected $codigoCliente;

    /**
     * Mostrar informações de recálculo no boleto
     *
     * @var bool
     */
    protected $mostrarRecalculado = false;

    /**
     * Mostrar informações de atualização no boleto
     *
     * @var bool
     */
    protected $mostrarAtualizado = false;

    /**
     * @return int
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param int $range
     *
     * @return Fibra
     */
    public function setRange($range)
    {
        $this->range = (int) $range;

        return $this;
    }

    /**
     * Define o número do codigo do Cliente
     *
     * @param string $codigoCliente
     * @return Fibra
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }

    /**
     * Retorna o número do codigo Cliente
     *
     * @return string
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }

    /**
     * Retorna o número definido pelo cliente para compor o nosso número
     *
     * @return int
     */
    public function getNumero()
    {
        return $this->numero < $this->getRange() ? $this->getRange() + $this->numero : $this->numero;
    }

    /**
     * Retorna o código da carteira (Com ou sem registro)
     *
     * @return string
     */
    public function getCarteira()
    {
        return $this->carteira == 'D' ? '121' : '112';
    }

    /**
     * Gera o Nosso Número.
     *
     * @return string
     */
    protected function gerarNossoNumero()
    {
        $nn = 0;
        if (Util::upper($this->carteira) == 'D') {
            $nn = $this->getNumero() . CalculoDV::fibraNossoNumero($this->getAgencia(), $this->getCarteira(), $nn);
        }

        return Util::numberFormatGeral($nn, 11);
    }

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $nossoNumero = $this->getNossoNumero();

        $campoLivre = Util::numberFormatGeral($this->getAgencia(), 4);
        $campoLivre .= Util::numberFormatGeral($this->getCarteira(), 3);
        $campoLivre .= Util::numberFormatGeral($this->getCodigoCliente(), 7);
        $campoLivre .= Util::numberFormatGeral($nossoNumero, 11);

        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @param $campoLivre
     *
     * @return array
     */
    public static function parseCampoLivre($campoLivre)
    {
        return [
            'convenio'        => null,
            'parcela'         => null,
            'agenciaDv'       => null,
            'contaCorrente'   => null,
            'modalidade'      => null,
            'contaCorrenteDv' => null,
            'nossoNumeroDv'   => null,
            'agencia'         => substr($campoLivre, 0, 4),
            'nossa_carteira'  => substr($campoLivre, 4, 3),
            'codigoCliente'   => substr($campoLivre, 7, 7),
            'nossoNumero'     => substr($campoLivre, 14, 11),
            'nossoNumeroFull' => substr($campoLivre, 14, 11),
        ];
    }

    /**
     * @return string
     */
    public function getAgenciaCodigoBeneficiario()
    {
        return sprintf('%s%s / %s%s', $this->getAgencia(), CalculoDV::fibraAgencia($this->getAgencia()), $this->getConta(), CalculoDV::fibraConta($this->getConta()));
    }

    /**
     * @return bool
     */
    public function imprimeBoleto()
    {
        return Util::upper($this->carteira) == 'D';
    }

    /**
     * Define se deve mostrar informações de recálculo no boleto
     *
     * @param bool $mostrarRecalculado
     * @return Fibra
     */
    public function setMostrarRecalculado($mostrarRecalculado = true)
    {
        $this->mostrarRecalculado = (bool) $mostrarRecalculado;

        return $this;
    }

    /**
     * Retorna se deve mostrar informações de recálculo no boleto
     *
     * @return bool
     */
    public function getMostrarRecalculado()
    {
        return $this->mostrarRecalculado;
    }

    /**
     * Define se deve mostrar informações de atualização no boleto
     *
     * @param bool $mostrarAtualizado
     * @return $this
     */
    public function setMostrarAtualizado($mostrarAtualizado = true)
    {
        $this->mostrarAtualizado = (bool) $mostrarAtualizado;
        return $this;
    }

    /**
     * Retorna se deve mostrar informações de atualização no boleto
     *
     * @return bool
     */
    public function getMostrarAtualizado()
    {
        return $this->mostrarAtualizado;
    }
}
