<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class printerService extends Controller
{
    var $Impresoranombre = "";
    public function print(Request $data)
    {
        $client = new \GuzzleHttp\Client();
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://' . $data->host . '/api/ventas/print/' . $data->id_venta);
        $Impresoranombre = $data->impresora;
        $promise = $client->sendAsync($request)->then(
            function ($response) use ($Impresoranombre)  {
                $venta = $response->getBody();
                $Ticket = json_decode($venta);
                $ticket = $Ticket->ticket;
                $venta = $Ticket->venta;

                $connector = new WindowsPrintConnector($Impresoranombre);
                $impresora = new Printer($connector);
                $impresora->setJustification(Printer::JUSTIFY_CENTER);
                $impresora->setTextSize(1.5, 1.5);
                if ($ticket->nombre_negocio) {
                    $impresora->text($ticket->nombre_negocio . "\n");
                }
                if ($ticket->direccion) {
                    $impresora->text("Domicilio: " . $ticket->direccion . "\n");
                }
                if ($ticket->num_telefono) {
                    $impresora->text("Telefono: " . $ticket->num_telefono . "\n");
                }
                if ($ticket->rfc) {
                    $impresora->text("RFC: " . $ticket->rfc . "\n");
                }
                $impresora->text("Ticket de Venta: #" . $venta->id_venta . "\n");
                $impresora->text("Atendio: " . $venta->user->name . "\n");
                $impresora->text("Fecha:");
                $impresora->text($venta->fecha . "\n");
                $impresora->setTextSize(1, 1);
                $impresora->text("--------------------------------\n");
                foreach ($venta->detalleventa as $detalle) {
                    $impresora->setJustification(Printer::JUSTIFY_LEFT);
                    $impresora->text($detalle->producto->nombre_producto . "\n");
                    $impresora->setJustification(Printer::JUSTIFY_RIGHT);
                    $impresora->text($detalle->cantidad . " x " . number_format($detalle->subtotal / $detalle->cantidad, 2, '.', '') . " = " . $detalle->subtotal . "\n");
                    if($detalle->tipoDescuento != ''){
                        $impresora->text("desc:"."-".$detalle->tipoDescuento .$detalle->descuentoValor. " -> $" .number_format($detalle->descuento ,2, '.', ''). "\n");
                    }
                }
                $impresora->text("                                \n");
                $impresora->setJustification(Printer::JUSTIFY_CENTER);
                $impresora->text("********************************\n");
                $impresora->text("                                \n");
                $impresora->setJustification(Printer::JUSTIFY_LEFT);
                $impresora->text("Cnt Prod:" . $venta->cantidad_productos . "     ");
                $impresora->setJustification(Printer::JUSTIFY_RIGHT);
                $impresora->text("Total: $" . number_format($venta->total_venta, 2, '.', '') . "\n");
                $impresora->text("Pago Requerido: $" . number_format($venta->pago[0]->pago->monto_a_pagar, 2, '.', '') . "\n");
                $descuento = 0;
                foreach($venta->detalleventa as $descuentoDtv){
                    $descuento += $descuentoDtv->descuento;
                }
                if($descuento != 0){
                    $impresora->text("Descuento: $" . number_format($descuento, 2, '.', '') . "\n");
                }
                $impresora->text("Pago c/ " . (strpos($venta->pago[0]->pago_type, 'efectivo') ? 'efectivo :' : 'tarjeta :'));
                $impresora->text("$" . number_format($venta->pago[0]->pago->monto_ingresado, 2, '.', '') . "\n");
                $impresora->text("Cambio: $" . number_format(($venta->pago[0]->pago->monto_a_pagar - $venta->pago[0]->pago->monto_ingresado), 2, '.', '') . "\n");
                if (Count($venta->pago) > 1) {
                    $impresora->text("Pago c/". (strpos($venta->pago[1]->pago_type, 'efectivo') ? 'Efectivo :'. number_format($venta->pago[1]->pago->monto, 2, '.', '')  : 'Tarjeta :' . number_format($venta->pago[1]->pago->monto, 2, '.', '') ) . "\n");
                }
                $impresora->setJustification(Printer::JUSTIFY_CENTER);
                $impresora->setTextSize(1.2, 1.2);
                $impresora->text("                                \n");
                $impresora->text("Fue un placer Atenderle \n");
                $impresora->feed(5);
                $impresora->pulse();
                $impresora->close();
            }
        );
        $promise->wait();
    }

    public function examples()
    {


        $connector = new WindowsPrintConnector('EC-PM-5890X');
        $impresora = new Printer($connector);
        $impresora->setJustification(Printer::JUSTIFY_CENTER);
        $impresora->setTextSize(2, 2);
        $impresora->text("Imprimiendo\n");
        $impresora->text("ticket\n");
        $impresora->text("desde\n");
        $impresora->text("Laravel\n");
        $impresora->setTextSize(1, 1);
        $impresora->text("https://parzibyte.me");
        $impresora->feed(5);
        $impresora->close();
    }
}
