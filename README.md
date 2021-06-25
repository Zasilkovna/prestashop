[Návod v češtině](#modul-pro-prestashop-17)

# Module for PrestaShop 1.7

### Download link
[Download the latest version](https://github.com/Zasilkovna/prestashop/releases/latest)

### Installation
1. Log in to the PrestaShop administration, click on the "Modules -> Module Manager" tab:

![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_1.png?raw=true)

2. In the upper right corner of the screen, click on "Load module":

![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_2.png?raw=true)

3. Click on "select file" and find the path to the downloaded module file:

![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_3.png?raw=true)

## Configuration
After successful installation, click on the **Orders** -> **Packeta Orders** tab in the main menu and select the **Settings** tab.

#### Module configuration
All settings are saved automatically whenever you leave the edited data. If you do not have a user account
on the Packeta site, it is possible to request a test account at the Customer Service email [info@zasilkovna.cz](mailto:info@zasilkovna.cz):
- **API key** – you can find your key in the client section of Packeta in the *Client support* tab
- **Sender name** - the sender name you have set in the client section of the list of senders

### Carrier settings
- To add a carrier enter the menu *Shipping* - *Carriers*.
- In the module settings, you select whether it is a *Packeta pickup point* or delivery to the address.
- If you select YES (checkmark) for the carrier in the "Is COD" column,
  the orders of this carrier will always be cash on delivery.
- If you want cash on delivery to be controlled by the payment method (see "Payment methods") and not the carrier,
  set NO (cross) in the "Is cash on delivery" column for the carrier and set cash on delivery in the "Is COD" column 
  for the payment method.
- For carriers whose shipments will not be transported by Packetery, select "--".

#### Set country restrictions
- In the carrier settings you specify for which zones is the selected carrier allowed and for what price.
- You can create these zones in the *International* - *Locations* - *Zones* menu and assign individual countries
  to them in the *International* - *Locations* - *Countries* menu.
- It is also necessary to enable selected payment modules in the menu *Payment* - *Preferences*
  in the *Country Restrictions* menu.

### Payment methods
- For each form of payment, you can set whether the payment is cash on delivery.
- If the payment method is cash on delivery in the **Is cash on delivery** column, click on the red cross that will
  automatically change to a green checkmark.

### Pickup points
You can find information about the last update of the list of pickup points in the tab **pickup points**:
- sum of pickup points
- date and time of the last update

The pickup points in the basket are updated automatically. Click on the button **Update pickup points**
to manually update the list of pickup points.

### Orders
In the **Orders** tab you will find a list of all orders for which the Packeta shipping method has been selected.
- For each packet, you can set/cancel cash on delivery in the "Cash on delivery" column.
- Marked orders can be saved to the CSV file by clicking on the **CSV export** button, which you will upload
  to the client section in » "Shipments import" afterward.
- The module also supports automatic sending of data to shipments. Send marked orders automatically by clicking on
  the button "Submit selected shipments." After successful shipment, the tracking number of the shipment will
  be automatically added. By clicking the tracking number you will open the tracking page.
- For shipments that have been submitted via automatic dispatch and have a tracking number filled in, you can
  print labels in PDF format. Mark orders and click on **Download pdf labels**.

## Information about the module

### Supported languages:
- czech
- english

### Supported versions
- PrestaShop version 1.7.x.
- If you have problems using the module, please contact us at [support@packeta.com](mailto:support@packeta.com).

### Provided functions
- Integration of [widget v6](https://widget.packeta.com/v6) for selection of pickup points in the e-shop cart.
- Support for pickup points of external carriers.
- Address delivery via Packeta external carriers.
- Information about the selected pickup point/carrier in the PrestaShop order detail.
- In the PrestaShop order detail, it is possible to change the selected pickup point using the v6 widget.
- Display of information about the selected pickup point in the last step of the order,
  in the "order confirmation" email, and the order detail view of the registered user.
- Automatic export of orders to the Packeta system.
- Export orders to the CSV file, which can be imported in the [client section](https://client.packeta.com/).

### OPC support
- One Page Supercheckout from [Knowband.com](https://www.knowband.com/prestashop-one-page-supercheckout?search=supercheckout)

In case you are using another third-party cart module, please write to [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz).
Packeta module may not work with another OPC module.

### Further module limitations
- Module does not currently support multistore.

# Modul pro PrestaShop 1.7

### Stažení modulu
[Aktuální verze (Stáhnout »)](https://github.com/Zasilkovna/prestashop/releases/latest)

### Instalace
1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly -> Module Manager".:

![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/1-moduly-75%25.png?raw=true)

2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":

![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/2-nahratmodul-75%25.png?raw=true)

3. Klikněte na "vybrat soubor" a najděte cestu ke staženému souboru modulu:

![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/3-nahrat-75%25.png?raw=true)

### Konfigurace

Po úspěšné instalaci klikněte v hlavním menu na záložku "Objednávky -> Zásilkovna Orders" a vyberte záložku "Nastavení":

![screen4](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/4-success-75%25.png?raw=true)

#### Nastavení modulu

Veškerá nastavení se ukládají automaticky, vždy po opuštění editovaného údaje.
V případě, že nemáte uživatelský účet na stránkách Zásilkovny, je možné v rámci testování funkcionality modulu či podávání zásilek požádat o testovací účet na mailu Zákaznického servisu: <info@zasilkovna.cz>:

- **Klíč API**  - váš klíč API naleznete v [klientské sekci Zásilkovny](https://client.packeta.com/cs/support/) v části **Klientská podpora**
- **Označení odesílatele** - označení odesílatele které máte nastaveno v [klientské sekci](https://client.packeta.com/cs/senders/) v seznamu odesílatelů

### Nastavení dopravců
- Dopravce vytvoříte v menu *Doručení* - *Dopravci*.
- V nastavení modulu vyberete, zda jde o *Výdejní místo Zásilkovny* nebo doručení na adresu.
- Pokud zvolíte u dopravce ve sloupci "Je dobírka" ANO (fajfka), budou objednávky s použitím
  tohoto dopravce vždy na dobírku.
- Pokud chcete, aby se dobírka řídila podle platební metody (viz "Způsoby platby"), a ne podle dopravce, nechte
  u dopravce ve sloupci "Je dobírka" NE (křížek).
- U dopravců, jejichž zásilky nebudou přepravované Zásilkovnou, zvolíte "--".

#### Nastavení omezení na zemi
- V nastavení dopravců určujete, pro které zóny je zvolený dopravce povolený a za jakou cenu.
- Tyto zóny můžete vytvořit v menu *Mezinárodní* - *Lokace* - *Zóny* a přiřadit do nich v menu
  *Mezinárodní* - *Lokace* - *Země* jednotlivé země.
- Dále je potřeba povolit vybrané platební moduly v menu *Platba* - *Konfigurace* v nabídce *Omezení pro země*.

### Způsoby platby
- U každého způsobu platby můžete nastavit zda se jedná o platbu na dobírku.
- Pokud se jedná o platební metodu na dobírku ve sloupci **Je na dobírku** klikněte na červený křížek,
  který se automaticky změní na zelené zatržítko.

### Výdejní místa
V záložce výdejní místa naleznete informace o poslední aktualizaci seznamu výdejních míst:
- celkový počet výdejních míst
- datum a čas poslední aktualizace

Výdejní místa v košíku se aktualizují automaticky. Pro ruční aktualizaci seznamu výdejních míst klikněte na tlačítko
**Aktualizace výdejních míst**.

### Objednávky
V záložce **objednávky** naleznete seznam všech objednávek u kterých byla vybrán způsob dopravy přes Zásilkovnu.
- U každé zásilky můžete ve sloupci **Je na dobírku** nastavit/zrušit odeslání na dobírku.
- Označené objednávky můžete kliknutím na tlačítko **CSV export** uložit do csv souboru, který poté nahrajete
  do [klientské sekce](https://client.packeta.com/) » Import zásilek.
- Modul podporuje také automatické odesílání dat k zásilkám.  Označené objednávky odešlete automaticky kliknutím
  na tlačítko **Podat vybrané zásilky**.  Po úspěšném odeslání se u zásilky doplní její trasovací číslo.  
  Kliknutím na trasovací číslo se otevře stránka sledování zásilky.
- U zásilek které byly podány přes automatické odeslání a mají vyplněné trasovací číslo můžete
  vytisknout štítky ve formátu pdf.  Objednávky označte a klikněte na tlačítko **Stažení štítků ve formátu .PDF**.

## Informace o modulu

### Podporované jazyky
- čeština
- angličtina

### Podporovaná verze
- PrestaShop verze 1.7.x.
- Při problému s použitím modulu nás kontaktujte na adrese [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz).

### Poskytované funkce
- Integrace widgetu pro výběr výdejních míst v košíku eshopu.
- Doručení na adresu přes externí dopravce Zásilkovny.
- Automatický export zásilek do systému Zásilkovny.
- Export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/).

#### Poskytované funkce
- Integrace [widgetu v6](https://widget.packeta.com/v6) pro výběr výdejních míst v košíku eshopu.
- Podpora výdejních míst externích dopravců.
- Doručení na adresu přes externí dopravce Zásilkovny.
- Informace o vybraném výdejním místě/dopravci v detailu objednávky PrestaShopu.
- V detailu objednávky PrestaShopu je možné změnit vybrané výdejní místo pomocí widgetu v6.
- Zobrazení informace o vybraném výdejní místě v posledním kroku objednávky, v emailu "potvrzení objednávky"
  a v detailu objednávky registrovaného uživatele.
- Automatický export zásilek do systému Zásilkovny.

### Podpora pro OPC
- One Page Supercheckout od [Knowband.com](https://www.knowband.com/prestashop-one-page-supercheckout?search=supercheckout)

Pokud používáte nějaký jiný modul košíku třetí strany, napište nám [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz).
Modul Zásilkovny nemusí být s jiným OPC modulem funkční.

### Další omezení modulu
- Modul v současné době nepodporuje multistore. 
  
