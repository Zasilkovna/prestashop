# Instalace Modulu

## Systémové požadavky
Pro instalaci Prestashop 1.7.x jsou vyžadovány následující komponenty:
- System: Unix, Linux, nebo Windows
- Web server: Apache 2.2 a novější; NGINX 1.0 a novější 
- PHP: doporučená verze 7.1 nebo 7.2; minimální 5.6
- MySQL: doporučená verze 5.6 a novejší; minimální 5.0
- Rozšíření PHP:
  - CURL
  - DOM
  - Fileinfo
  - GD
  - Intl
  - Zip
- Nastavení PHP: allow_url_fopen povoleno
- Pro více informací navštivte https://devdocs.prestashop.com/1.7/basics/installation/system-requirements/
        
## Instalace a úvodní konfigurace 
Pro instalaci stačí poprvé otevřít Prestashop v prohlížeči a projít instalačním formulářem.
Pro úspěšnou instalaci je třeba, aby měl webserver právo k zápisu do potřebných složek. 
Databázi je možné vytvořit předem, nebo zvolit automatické vytvoření při instalaci.

_!!! Pokud používáte českou verzi e-shopu, navigujte se po instalaci do sekce KONFIGURACE->SEO A URLS, vyhledejte "objednavka" ve filtru "Přátelské URL" a 
ujistěte se, že stránky order a order-opc mají rozlišné přátelské URL. Pokud tak neučiníte, proces objednávky bude náchylný na chyby přesměrování !!!_

## Instalace plug-inu pro Zásilkovnu
 Pro instalaci plug-inu je potřeba provést následující kroky:
- Ujistit se, že má webserver práva na zápis do složky /modules 
- Přihlásit se do administrace Prestashopu na adrese host/adminXXXXXX (vygenerováno automaticky, adresu administrace zjistíte dle jména složky)
- V sekci "Module Manager" nebo "Katalog Modulů" kliknout na tlačítko "Nahrát modul" v pravém horním rohu
- Vybrat .zip archiv modulu

Po těchto krocích je plug-in nainstalovaný. Dále je potřeba provést základní konfiguraci, v nastavení modulu přejděte do sekce "Nastavení" a vyplňte následující:
- API heslo (získáte po přihlášení na http://client.packeta.com/)
- API klíč (první polovina API hesla)
- Vytvořte dopravce v tabulce "Přidání způsobu dopravy" ve spodní části stránky.

Veškerá konfigurace se ukládá automaticky.

## Konfigurace plug-inu
### Základní konfigurace
 - Nejprve se zaregistrujte na http://client.packeta.com/ a zkopírujte API heslo do pole na kartě modulu.
 - Následně zkopírujte první polovinu hesla do pole "API Klíč" - ten je poté použit k přístupu k widgetu Zásilkovny v objednávkovém procesu.
 - Všechna nastavení se automaticky ukládají.
 - Pro přidání nového dopravce klikněte na tlačítko "+" v pravém horním rohu tabulky "PŘIDÁNÍ ZPŮSOBU DOPRAVY"
 - Můžete změnit nastavení dopravce a platby COD pomocí tlačítka. Na všechna nastavení COD v tabulkách lze kliknout.
 - Je možné vynutit použití specifické země a jazyka ve widgetu nastavením hodnot "Vynucená země" a "Vynucený jazyk". Pokud jsou hodnoty prázdné, vybere se země podle adresy zákazníka..
 - Pokud používáte Multistore, políčka eshopu se automaticky vyplní aktuálním názvem obchodu, který je nastaven v administrátorském panelu. 
 - K export dat do CSV souboru zvolte objednávky, které chcete vyexportovat na záložce "Objednávky" a klikněte na tlačítko "CSV Export"
