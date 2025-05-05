[Návod v češtině](#modul-pro-prestashop-17-prestashop-8)

# Module for PrestaShop 1.7, PrestaShop 8

### Download link
[Download the latest version](https://github.com/Zasilkovna/prestashop/releases/latest)

### Installation/Upgrade
The installation and upgrade procedures are the same. If you are updating, we strongly recommend that you first
backup the database and module (folder `modules/packetery`).

1. Log in to the PrestaShop administration, click on the "Modules -> Module Manager" tab:

![screen1](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/screenshot_1.png?raw=true)

2. In the upper right corner of the screen, click on "Load module":

![screen2](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/screenshot_2.png?raw=true)

3. Click on "select file" and find the path to the downloaded module file:

![screen3](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/screenshot_3.png?raw=true)

## Configuration
After successful installation, click on the **Orders** -> **Packeta Orders** tab in the main menu and select the **Settings** tab.

#### Module configuration
All settings are saved by submitting the form.

You have to fill in the following required information:
- **API password** – you can find your password in the client section of Packeta in the *Client support* tab
- **Sender name** - the sender name you have set in the client section of the list of senders

### Carrier settings
- To add a carrier enter the menu *Shipping* - *Carriers*.
- In the carrier settings you specify for which zones is the selected carrier allowed and for what price.
- You can create these zones in the *International* - *Locations* - *Zones* menu and assign individual countries
  to them in the *International* - *Locations* - *Countries* menu.
- It is also necessary to enable selected payment modules in the menu *Payment* - *Preferences*
  in the *Country Restrictions* menu.

#### Carrier settings on the module side
- In the carrier settings of the module, you can update the list of Packeta carriers by clicking on the button,
  it is also possible to set up automatic updates.
- You can also select for each carrier whether it is a *Packeta pickup points* or an external carrier.
- For carriers whose shipments will not be transported by Packeta, select "--".

### Payment methods
- For each form of payment, you can set whether the payment is cash on delivery.
- If the payment method is cash on delivery in the **Is cash on delivery** column, click on the red cross that will
  automatically change to a green checkmark.

### Orders
In the **Orders** tab you will find a list of all orders for which the Packeta shipping method has been selected.
- The module supports automatic sending of data for shipments. You can send checked orders automatically by clicking
  on **Bulk actions** and **Send selected orders and create shipment**. After the data has been sent, a tracking number will be added to the shipment.
  By clicking on the tracking number, the shipment tracking page will open. Shipments can also be submitted one by one with one click.
- For shipments that were submitted via automatic shipment and have a tracking number, you can
  print labels in PDF format. Check the orders and click on **Bulk actions** and **Download Packeta labels**,
  or **Download carrier labels**. Labels can also be prepared one by one with one click.
- In the **Bulk actions** menu you will also find **CSV export**, which you can upload to
  [client section](https://client.packeta.com/) » Parcels import.

## Information about the module

### Supported languages:
- czech
- english

### Supported versions
- PrestaShop version 1.7, Prestashop 8
- PHP 7.1 - PHP 8.1
- If you have problems using the module, please contact us at [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com).

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
- It is now possible to set the weight of the shipment in the list of Packeta orders, before sending it to Packeta
  (since version 2.1.9).
- Optional or mandatory validation of the delivery address using a widget when delivering to the Czech Republic or Slovakia.
- Set shipment dimensions on order detail page.
- Print labels and carrier labels from order overview.
- Multistore support.
- Set age verification for orders containing adult products.
- Ability to set default shipment weight and packaging material weight.

### OPC support
- One Page Supercheckout from [Knowband.com](https://www.knowband.com/prestashop-one-page-supercheckout?search=supercheckout) (since version 6.0.9)

In case you are using another third-party cart module, please write to [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com).
Packeta module may not work with another OPC module.

# Modul pro PrestaShop 1.7, PrestaShop 8

### Stažení modulu
[Aktuální verze (Stáhnout »)](https://github.com/Zasilkovna/prestashop/releases/latest)

### Instalace/Aktualizace
Postup pro instalaci a aktualizaci je stejný. Pokud provádíte aktualizaci důrazně doporučujeme provést
nejdříve zálohu databáze a modulu (složka `modules/packetery`).

1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly -> Module Manager".:

![screen1](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/1-moduly-75%25.png?raw=true)

2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":

![screen2](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/2-nahratmodul-75%25.png?raw=true)

3. Klikněte na "vybrat soubor" a najděte cestu ke staženému souboru modulu:

![screen3](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/3-nahrat-75%25.png?raw=true)

### Konfigurace

Po úspěšné instalaci klikněte v hlavním menu na záložku "Objednávky -> Zásilkovna Orders" a vyberte záložku "Nastavení":

![screen4](https://github.com/Zasilkovna/prestashop/blob/main/doc/img/4-success-75%25.png?raw=true)

#### Nastavení modulu

Veškerá nastavení se ukládají odesláním formuláře.

Je nutné vyplnit tyto povinné údaje:
- **API heslo** - vaše API heslo naleznete v [klientské sekci Zásilkovny](https://client.packeta.com/cs/support/) v části **Klientská podpora**
- **Označení odesílatele** - označení odesílatele které máte nastaveno v [klientské sekci](https://client.packeta.com/cs/senders/) v seznamu odesílatelů

### Nastavení dopravců v PrestaShopu
- Dopravce vytvoříte v menu *Doručení* - *Dopravci*.
- V nastavení dopravců určujete, pro které zóny je zvolený dopravce povolený a za jakou cenu.
- Tyto zóny můžete vytvořit v menu *Mezinárodní* - *Lokace* - *Zóny* a přiřadit do nich v menu
  *Mezinárodní* - *Lokace* - *Země* jednotlivé země.
- Dále je potřeba povolit vybrané platební moduly v menu *Platba* - *Konfigurace* v nabídce *Omezení pro země*.

#### Nastavení dopravců na straně modulu
- V nastavení dopravců modulu aktualizujete seznam dopravců Zásilkovny stiknutím tlačítka,
  je možné i nastavit automatickou aktualizaci.
- Dále může pro každého dopravce vybrat, zda jde o *Výdejní místa Zásilkovny* nebo externího dopravce.
- U dopravců, jejichž zásilky nebudou přepravované Zásilkovnou, zvolíte "--".

### Způsoby platby
- U každého způsobu platby můžete nastavit zda se jedná o platbu na dobírku.
- Pokud se jedná o platební metodu na dobírku ve sloupci **Je na dobírku** klikněte na červený křížek,
  který se automaticky změní na zelené zatržítko.

### Objednávky
V záložce **objednávky** naleznete seznam všech objednávek u kterých byla vybrán způsob dopravy přes Zásilkovnu.
- Modul podporuje automatické odesílání dat k zásilkám. Označené objednávky odešlete automaticky kliknutím
  na **Hromadné akce** a **Podat vybrané zásilky**. Po úspěšném odeslání se u zásilky doplní její trasovací číslo.  
  Kliknutím na trasovací číslo se otevře stránka sledování zásilky. Zásilky je možné podávat i po jedné jedním kliknutím.
- U zásilek které byly podány přes automatické odeslání a mají vyplněné trasovací číslo můžete
  vytisknout štítky ve formátu PDF. Objednávky označte a klikněte na **Hromadné akce** a **Stáhnout štítky Zásilkovny**,
  případně **Stáhnout štítky dopravce**. Štítky je možné připravovat i po jednom jedním kliknutím.
- V nabídce **Hromadné akce** naleznete také **CSV export**, který můžete nahrát do
  [klientské sekce](https://client.packeta.com/) » Import zásilek.

## Informace o modulu

### Podporované jazyky
- čeština
- angličtina

### Podporovaná verze
- PrestaShop verze 1.7, PrestaShop 8
- PHP 7.1 - PHP 8.1
- Při problému s použitím modulu nás kontaktujte na adrese [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com).

### Poskytované funkce
- Integrace [widgetu v6](https://widget.packeta.com/v6) pro výběr výdejních míst v košíku eshopu.
- Podpora výdejních míst externích dopravců.
- Doručení na adresu přes externí dopravce Zásilkovny.
- Informace o vybraném výdejním místě/dopravci v detailu objednávky PrestaShopu.
- V detailu objednávky PrestaShopu je možné změnit vybrané výdejní místo pomocí widgetu v6.
- Zobrazení informace o vybraném výdejní místě v posledním kroku objednávky, v emailu "potvrzení objednávky"
  a v detailu objednávky registrovaného uživatele.
- Automatický export zásilek do systému Zásilkovny.
- Export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/).
- V seznamu objednávek Zásilkovny je nyní možné upravit hmotnost zásilky, před odesláním do Zásilkovny (od verze 2.1.9).
- Volitelná nebo povinná validace doručovací adresy pomocí widgetu při doručení do Česka nebo na Slovensko.
- Nastavení rozměrů zásilky na detailu objednávky.
- Tisk štítků a štítků dopravců z přehledu objednávek.
- Podpora multistore.
- Nastavení ověření věku pro objednávky obsahující produkty pro dospělé.
- Možnost nastavit výchozí hmotnost zásilky a obalového materiálu.

### Podpora pro OPC
- One Page Supercheckout od [Knowband.com](https://www.knowband.com/prestashop-one-page-supercheckout?search=supercheckout) (od verze 6.0.9)

Pokud používáte nějaký jiný modul košíku třetí strany, napište nám [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com).
Modul Zásilkovny nemusí být s jiným OPC modulem funkční.
