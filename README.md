# Modul pro PrestaShop

## Stažení modulu pro Prestashop 1.7
<a href="https://github.com/Zasilkovna/prestashop/raw/master/releases/packetery-latest.zip">Stažení instalačního balíčku</a>

## Modul pro starší Prestashop
<a href="https://github.com/Zasilkovna/prestashop/raw/master/releases/packetery-1.18.zip">Verzi 1.18 (stáhnout »)</a>.

Modul s úpravou pro kompatibilitu s Easypay (Prestashop 1.6.x) naleznete zde: https://github.com/Zasilkovna/prestashop/tree/easypay

## Informace o modulu

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

## Instalace modulu
1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly":
[![screen1](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-moduly.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-moduly.png)
2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":
[![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-nahratmodul.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-nahratmodul.png)
3. Do nově otevřeného okna přetáhněte stažený soubor modulu nebo klikněte na "vyberte soubor" a najděte cestu ke staženému souboru modulu:
[![screen3](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nahrat.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nahrat.png)
4. Po úspěšné instalaci vstupte do konfigurace modulu kliknutím na "Konfigurace":
[![screen4](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-success.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-success.png)
5. V konfiguraci modulu přejděte do záložky "Nastavení":
[![screen5](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-nastaveni.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-nastaveni.png)
6. V sekci "Nastavení" je nutné zadat heslo API. Vaše heslo API najdete v klientské sekci, v záložce [Můj účet](http://www.zasilkovna.cz/muj-ucet). V případě, že nemáte uživatelský účet na stránkách Zásilkovny, je možné, v rámci testování funkcionality modulu či podávání zásilek, požádat o testovací účet na mailu Zákaznického servisu: <info@zasilkovna.cz>:
[![screen6](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-hesloAPI.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-hesloAPI.png)
7. Následujícím krokem je vytvoření způsobu dopravy, který provedete kliknutím na symbol "+" v tabulce "Přidání způsobu dopravy":
[![screen7](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-dopravce.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-dopravce.png)
8. Otevře se vyskakovací okno, vlastnosti způsobu dopravy nastavte dle Vašich požadavků. Vybrat více zemí je možné přidržením tlačítka "Ctrl" a kliknutím na jednotlivé požadované země:
[![screen8](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/8-dopravapopup.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/8-dopravapopup.png) 
9. Způsob dopravy uložte, stránka se automaticky přesměruje zpět na úvodní stránku modulu, v záložce "Nastavení" zkontrolujte správně vytvořený způsob dopravy:
[![screen9](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/9-done.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/9-done.png)

TODO: Externí dopravci
