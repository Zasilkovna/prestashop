# Modul pro PrestaShop

### Obsah:
#### PrestaShop 1.7
*   [Informace o modulu](https://github.com/Zasilkovna/prestashop#informace-o-modulu)
*   [Stažení modulu](https://github.com/Zasilkovna/prestashop#sta%C5%BEen%C3%AD-modulu)
*   [Instalace modulu](https://github.com/Zasilkovna/prestashop#instalace-modulu)
*   [Nastavení dopravce doručení na adresu](https://github.com/Zasilkovna/prestashop#nastaven%C3%AD-dopravce-doru%C4%8Den%C3%AD-na-adresu)

#### PrestaShop 1.6.x
*   [Informace o modulu](https://github.com/Zasilkovna/prestashop#informace-o-modulu-1)
*   [Stažení modulu](https://github.com/Zasilkovna/prestashop#sta%C5%BEen%C3%AD-modulu-1)
*   [Instalace modulu](https://github.com/Zasilkovna/prestashop#instalace-modulu-1)


## PrestaShop 1.7

### Informace o modulu
TODO

### Stažení modulu
[Aktuální verze (Stáhnout »)](https://github.com/Zasilkovna/prestashop/raw/master/releases/packetery-latest.zip)

### Instalace modulu
1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly":

![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/1-moduly-75%25.png)

2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":

![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/2-nahratmodul-75%25.png)

3. Do nově otevřeného okna přetáhněte stažený soubor modulu nebo klikněte na "vyberte soubor" a najděte cestu ke staženému souboru modulu:

![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/3-nahrat-75%25.png)

4. Po úspěšné instalaci vstupte do konfigurace modulu kliknutím na "Konfigurace":

![screen4](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/4-success-75%25.png)

5. V konfiguraci modulu přejděte do záložky "Nastavení":

![screen5](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/5-nastaveni-75%25.png)

6. V sekci "Nastavení" je nutné zadat heslo API. Vaše heslo API najdete v klientské sekci, v záložce [Můj účet (Přejít »)](http://www.zasilkovna.cz/muj-ucet). V případě, že nemáte uživatelský účet na stránkách Zásilkovny, je možné v rámci testování funkcionality modulu či podávání zásilek požádat o testovací účet na mailu Zákaznického servisu: <info@zasilkovna.cz>:

![screen6](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/6-hesloAPI-75%25.png)

7. Následujícím krokem je vytvoření způsobu dopravy, který provedete kliknutím na symbol "+" v tabulce "Přidání způsobu dopravy":

![screen7](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/7-dopravce-75%25.png)

8. Otevře se vyskakovací okno, vlastnosti způsobu dopravy nastavte dle Vašich požadavků. Vybrat více zemí je možné přidržením tlačítka "Ctrl" a kliknutím na jednotlivé požadované země:

![screen8](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/8-dopravapopup-75%25.png)

9. Způsob dopravy uložte, stránka se automaticky přesměruje zpět na úvodní stránku modulu, v záložce "Nastavení" zkontrolujte správně vytvořený způsob dopravy:

![screen9](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/9-done-75%25.png)

### Nastavení dopravce doručení na adresu
TODO

## Prestashop 1.6.x

### Informace o modulu

**Podporované jazyky:**

* čeština
* angličtina

**Poskytované funkce:**

* Instalace typu dopravce Zásilkovna
  * možnost rozlišení ceny dle cílové země (pomocí vícenásobné instalace dopravce)
  * možnost instalace jedné země vícekrát (např. určujete-li dobírku již v přepravci)
  * volba typu zobrazení stejná jako v průvodci vložením poboček (JS API)
  * vybraná pobočka se zobrazuje v detailu objednávky v uživatelské (front-office) i administrátorské (back-office) sekci a dále v informačním e-mailu o objednávce
  * podpora doručení na adresu přes Zásilkovnu (zahraničí, večerní expresy apod.)

* Možnost exportu souboru s objednávkami
  * možnost označit objednávky, export CSV souboru pro hromadné podání zásilek
  * vyznačení již exportovaných objednávek
  * automatické a manuální označení dobírek
  * konverze měn pomocí interních kurzů PrestaShopu při zasílání za hranice (i pokud nepoužíváte dobírky do zahraničí, vypočte se z objednávky alespoň hodnota zásilky pro účely pojištění)

* Modul nepodporuje multistore

### Stažení modulu
[Aktuální verze 1.18 (Stáhnout »)](https://github.com/Zasilkovna/prestashop/raw/master/releases/packetery-1.18.zip)

[Easypay (Přejít »)](https://github.com/Zasilkovna/prestashop/tree/easypay) Modul s úpravou pro kompatibilitu s Easypay

### Instalace modulu
1. Stáhnout soubor modulu
2. Přihlašte se do administrace PrestaShopu, otevřete kartu Moduly (Modules) zde v horní části dejte přidat nový modul – tento stažený soubor:

  [![screen1](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-stazeni.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-stazeni.png)

3. Stažený modul nainstalujte:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-instalace.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-instalace.png)

4. Mělo se zobrazit hlášení o úspěšné instalaci a výstražná ikona o varováních, což je v pořádku, bude nutné ještě provést nastavení:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nainstalovano-varovani.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nainstalovano-varovani.png)

5. V nastavení je nutné zadat klíč API. Váš klíč API najdete ve své klientské sekci, pod Můj účet:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-klic-api.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-klic-api.png)

6. Posledním krokem je vytvoření způsobu dopravy. Toto provedete taktéž v nastavení modulu:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-zpusob-dopravy.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-zpusob-dopravy.png)

  Poté již můžete modul Zásilkovna plně využívat.

7. Dále až budete mít nějaké objednávky se způsobem dopravy Zásilkovna, můžete si je exportovat v CSV formátu pro hromadné podání zásilek:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-export-objednavek.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-export-objednavek.png)

8. Pokud využíváte doručení na adresu přes Zásilkovnu (např. do zahraničí), můžete standardně vytvořit nový způsob dopravy v PrestaShopu (nového dopravce, zcela mimo modul Zásilkovny) a v nastavení modulu Zásilkovny určit pod jakým způsobem dopravy se tento má exportovat pro systém Zásilkovny – objednávky se pak zobrazí na stejném místě jako objednávky na výdejní místa (viz minulý bod):

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-doruceni-na-adresu.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-doruceni-na-adresu.png)
