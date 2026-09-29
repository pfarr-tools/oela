# Datenschutzhinweise

## Verantwortliche Stelle

Verantwortlich für die Verarbeitung personenbezogener Daten in OELA ist:

Evangelische Kirchengemeinde Gäufelden  
Vertreten durch den geschäftsführenden Pfarrer Christoph Fischer  
Buchenstraße 29  
71126 Gäufelden-Nebringen  
Telefon: +49 7032 75567  
[pfarramt.nebringen@elkw.de](mailto:pfarramt.nebringen@elkw.de)

Für die verantwortliche Stelle ist außerdem der örtliche Beauftragte für den
Datenschutz erreichbar:

schwinge GmbH, Christian Schwinge  
Am Kochenhof 12  
70192 Stuttgart  
Telefon: +49 (0)711 / 25 85 60-0  
[DSBISB.ELKW@schwinge.com](mailto:DSBISB.ELKW@schwinge.com)  
[www.schwinge.com](https://www.schwinge.com)

## Umfang und Zweck der Verarbeitung

OELA verwaltet Anmeldungen für Termine des Lebendigen Adventskalenders in den
konfigurierten Orten. Bei einer Anmeldung verarbeiten wir den Namen, die
Ortsangabe, die Telefonnummer, optional die E-Mail-Adresse, den ausgewählten
Tag und Ort sowie Zeitstempel der Speicherung und Änderung.

Die Daten werden zur Organisation des Termins, zur Kontaktaufnahme bei
Rückfragen und zur Verwaltung der belegten Tage verarbeitet. Eine Anmeldung
ist ohne die erforderlichen Angaben nicht möglich.

## Veröffentlichung

Die Veröffentlichung von Name und Ortsangabe ist nur aktiviert, wenn die
entsprechende Einwilligung im Formular erteilt wurde. Freigegebene Termine
werden im öffentlichen Kalender-Embed ausgegeben. Telefonnummer und
E-Mail-Adresse werden dort niemals ausgegeben.

Die Einwilligung kann für die Zukunft widerrufen werden. Außerdem kann die
Verwaltung einen Eintrag löschen oder die Veröffentlichung im Admin-Formular
deaktivieren.

## Speicherung in OELA

Die Anmeldedaten werden in einer SQLite-Datenbank gespeichert, die der
Betreiber auf dem Server im persistenten Datenverzeichnis von OELA verwaltet.
Die Datenbank ist nicht öffentlich abrufbar. OELA gibt personenbezogene Daten
nicht zu Werbe- oder Analysezwecken weiter und enthält keine Benutzerkonten.

Die Admin-Oberfläche ist über dauerhafte, HMAC-signierte Links geschützt.
Wer einen solchen Link besitzt, kann die Anmeldedaten des zugehörigen Ortes
verwalten. Admin-Links dürfen deshalb nicht weitergegeben werden.

Für Formulare und die Admin-Oberfläche verwendet OELA eine technisch
notwendige PHP-Session zur CSRF-Absicherung. Es werden keine Analyse- oder
Werbe-Cookies durch OELA gesetzt.

## Server- und Zugriffsprotokolle

Beim Aufruf können der vorgeschaltete Webserver und der Container technische
Zugriffsdaten verarbeiten, insbesondere IP-Adresse, Datum und Uhrzeit,
angeforderte URL, HTTP-Status, Referrer sowie Browser- und
Betriebssysteminformationen. Die Protokollierung dient dem sicheren Betrieb,
der Fehleranalyse und dem Schutz vor Missbrauch. OELA schreibt selbst keine
personenbezogenen Anmeldedaten in Anwendungslogs. Aufbewahrungsdauer und
Löschung der technischen Serverprotokolle richten sich nach der Konfiguration
des Betreibers und den gesetzlichen Pflichten.

## Externe Ressourcen

Die vollständigen HTML-Seiten laden das CSS-Framework Bootstrap über das
Content-Delivery-Network jsDelivr. Beim Laden dieser Ressource kann jsDelivr
technische Verbindungsdaten, insbesondere die IP-Adresse, erhalten. Die
öffentlichen Embed-Fragmente laden keine externen Ressourcen und benötigen
weder Bootstrap noch JavaScript.

OELA verwendet keine Matomo-Analyse, keinen Newsletter-Dienst, kein YouTube,
keinen externen Gemeindeverwaltungsdienst und kein Consent-Management-Tool.

## Rechtsgrundlagen und Speicherdauer

Die Verarbeitung erfolgt im kirchlichen Bereich insbesondere auf Grundlage
des Datenschutzgesetzes der Evangelischen Kirche in Deutschland (DSG-EKD),
insbesondere zur Durchführung der Anmeldung und zur Wahrnehmung kirchlicher
Aufgaben. Die Veröffentlichung von Name und Ortsangabe beruht zusätzlich auf
der erteilten Einwilligung.

Die Daten werden gelöscht, sobald der Zweck der Verarbeitung entfällt, sofern
keine gesetzlichen Aufbewahrungspflichten oder berechtigten Gründe für eine
längere Speicherung entgegenstehen. OELA verfügt über keine automatische
Löschfrist; die Verwaltung kann Einträge über die Admin-Oberfläche löschen.

## Ihre Rechte

Sie haben nach Maßgabe des DSG-EKD insbesondere das Recht auf Auskunft,
Berichtigung, Löschung, Einschränkung der Verarbeitung,
Datenübertragbarkeit und Widerspruch. Eine erteilte Einwilligung kann jederzeit
mit Wirkung für die Zukunft widerrufen werden. Die Rechtmäßigkeit der bis zum
Widerruf erfolgten Verarbeitung bleibt unberührt.

Sie haben außerdem das Recht, sich bei der zuständigen Datenschutzaufsicht zu
beschweren:

Beauftragter für den Datenschutz der EKD  
Michael Jacob  
Lange Laube 20  
30159 Hannover  
Telefon: +49 (0)511 768128-0  
[info@datenschutz.ekd.de](mailto:info@datenschutz.ekd.de)

Außenstelle für die Datenschutzregion Süd  
Hafenbad 22  
89073 Ulm  
Telefon: +49 (0)731 140593-0  
[sued@datenschutz.ekd.de](mailto:sued@datenschutz.ekd.de)
