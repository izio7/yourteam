<?php

/**
 * Created by PhpStorm.
 * User: luigidurso
 * Date: 19/12/16
 * Time: 10:02
 */

namespace AppBundle\it\unisa\formazione;

use AppBundle\it\unisa\account\AccountCalciatore;
use AppBundle\it\unisa\account\DettagliCalciatore;
use \AppBundle\Utility\DB;
use Symfony\Component\Config\Definition\Exception\Exception;

class GestioneRosa
{
    private $db;
    private $connessione;
    private static $instance = null;

    //inizio connessione al database alla costruzione del gestore
    private function __construct()
    {
        $this->db=DB::getInstance();
        $this->connessione=$this->db->connect();
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Metodo che prende tutti i calciatori della squadra dal db e li restituisce
     * @return array
     */
    public function visualizzaRosa($squadra)
    {

        $query="SELECT * FROM Calciatore WHERE squadra='$squadra'";
        $risultato=$this->connessione->query($query);

        if($risultato->num_rows<=0) throw new Exception("nessun calciatore trovato per la squadra".$squadra);

        while ($calciatore=$risultato->fetch_assoc())
        {
            $obj=new AccountCalciatore($calciatore["contratto"],$calciatore["password"],$calciatore["squadra"],$calciatore["email"],$calciatore["nome"],$calciatore["cognome"],$calciatore["datadinascita"],$calciatore["domicilio"],$calciatore["indirizzo"],$calciatore["provincia"],$calciatore["telefono"],$calciatore["immagine"],$calciatore["nazionalita"]);

            $calciatori[]=$obj;
        }
        return $calciatori;

    }

    /**
     * Ritorna tutte le tattiche predefinite inserite nel db
     */
    public function visualizzaTattica()
    {
        $query="SELECT * FROM modulo";

        $risultato=$this->connessione->query($query);

        if($risultato->num_rows<=0) throw new Exception("errore accesso alle tattiche nel db!");

        while($tattica=$risultato->fetch_assoc())
        {
            $tattiche[]=$tattica["id"];
        }

        return $tattiche;


    }

    /**
     * metodo che prende in input i contratti dei calciatori schierati in campo
     * e li notifica con una email informativa
     * @param $calciatori
     */
    public function inviaEmailSchieramentoFormazione($calciatori)
    {
        foreach ($calciatori as $contratto)
        {
            $query="SELECT * FROM calciatore WHERE contratto='$contratto'";

            $risultato=$this->connessione->query($query);
            if($risultato->num_rows<=0) throw new Exception("nessun calciatore trovato!");

            if($risultato->num_rows==1)
            {
                $riga=$risultato->fetch_assoc();
                $emailCalciatore=$riga["email"];

                /*invio email da implementare in attesa di account email per il sistema
                $message = \Swift_Message::newInstance()
                    ->setSubject("Schierato")
                    ->setFrom("")
                    ->setTo($emailCalciatore)
                    ->setBody("Sei stato schierato per la prossima partita","text/plain")

                ;
                $this->get('mailer')->send($message);
                */
            }
        }

    }

    /**
     * Metodo che ritorna i calciatori convocati per una partita
     * specificata in input
     * @param $partita
     */
    public function ottieniConvocati($partita)
    {
        $nomePartita=$partita->getNome();
        $dataPartita=$partita->getData();
        $squadra=$partita->getSquadra();
        //seleziono tutti i calciatori convocati per questa partita
        $query="SELECT * FROM calciatore JOIN giocare ON calciatore.contratto=giocare.calciatore WHERE data='$dataPartita' AND partita='$nomePartita' AND giocare.squadra='$squadra'";

        $risultato=$this->connessione->query($query);

        if($risultato->num_rows<=0) throw new Exception("nessun convocato trovato!");

        while ($dettaglioCalciatore=$risultato->fetch_assoc())
        {
            $convocato=new DettagliCalciatore($dettaglioCalciatore["contratto"],null,null,null,$dettaglioCalciatore["nome"],$dettaglioCalciatore["cognome"],null,null,null,null,null,null,null,null,null,null,null,null,null);
            //per ogni calciatore setto l'array di ruoli
            $contratto=$dettaglioCalciatore["contratto"];
            $queryRuoli="SELECT * FROM conosce WHERE calciatore='$contratto'";

            $risultatoRuoli=$this->connessione->query($queryRuoli);

            //if($risultatoRuoli->num_rows<=0) throw new Exception("nessun ruolo trovato!");

            if($risultatoRuoli->num_rows>0)
            {
                while ($ruolo=$risultatoRuoli->fetch_assoc())
                {
                    $convocato->aggiungiRuolo($ruolo["ruolo"]);
                }
                $convocati[]=$convocato;
            }


        }
        return $convocati;
    }

    /**
     * Metodo che prende in input una tattica
     * e ne restituisce un model "Modulo"
     * @param $tattica
     */
    public function ottieniTattica($tattica)
    {
        $query="SELECT * FROM composto JOIN ruolo  ON composto.ruolo=ruolo.id WHERE modulo='$tattica' ORDER BY ripetizioneRuolo";

        $risultato=$this->connessione->query($query);

        if($risultato->num_rows<=0) throw new Exception("nessun modulo trovato!");

        $modulo=new Modulo($tattica,null);

        $difensori=null;
        $mediani=null;
        $centrocampisti=null;
        $trequartisti=null;
        $attaccanti=null;

        while ($riga=$risultato->fetch_assoc())
        {
            if(($riga["descrizione"])=="Difensore")
            {
                $difensori[]=$riga["ruolo"];
            }
            if(($riga["descrizione"])=="Mediano")
            {
                $mediani[]=$riga["ruolo"];
            }
            if(($riga["descrizione"])=="Centrocampista")
            {
                $centrocampisti[]=$riga["ruolo"];
            }
            if(($riga["descrizione"])=="Trequartista")
            {
                $trequartisti[]=$riga["ruolo"];
            }
            if(($riga["descrizione"])=="Attaccante")
            {
                $attaccanti[]=$riga["ruolo"];
            }
        }

        $modulo->setDifensori($difensori);
        $modulo->setMediani($mediani);
        $modulo->setCentrocampisti($centrocampisti);
        $modulo->setTrequartisti($trequartisti);
        $modulo->setAttaccanti($attaccanti);

        return $modulo;

    }


    function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->db->close($this->connessione);
    }


}