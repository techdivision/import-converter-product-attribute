# AGENTS.md - import-converter-product-attribute

## Zweck & Verantwortung

Das `import-converter-product-attribute` Modul bietet **Product CSV zu Attribute Option CSV Konvertierung**. Es ist ein **Tier 5 Modul** und erweitert `import-converter`.

**Hauptverantwortung:**
- Transformation von Product CSV zu Attribute Option CSV
- Observer Pattern für Konvertierungs-Hooks
- Event-Driven für Konvertierungs-Prozesse
- Listener für Custom Processing

## Architektur & Design Patterns

### Kern-Klassen
- **ProductAttributeConverter**: Haupt-Converter-Klasse
- **ProductAttributeConverterObserver**: Observer für Hooks
- **ProductAttributeConverterListener**: Listener für Events

### Verwendete Patterns
- **Observer Pattern**: Für Konvertierungs-Hooks
- **Event-Driven**: Für Konvertierungs-Prozesse
- **Strategy Pattern**: Verschiedene Konvertierungs-Strategien

## Abhängigkeiten

### Externe Pakete
- **Keine**

### TechDivision Dependencies
- **import-product** ^26.0.0 - Product Importer
- **import-attribute** ^23.0.0 - Attribute Importer
- **import-converter** ^12.0.0 - Converter Framework

### Abhängig von diesem Modul (1 Reverse Dependency)
- **import-cli-simple** - Master CLI

## Wichtige Entry Points

### Converter Klassen
```php
// Product Attribute Converter
ProductAttributeConverter::convert($row): array
ProductAttributeConverter::getSubject(): SubjectInterface

// Converter Observer
ProductAttributeConverterObserver::handle($row): void
```

## Events & Extension Points

### Events
- **BeforeConversionEvent**: Vor Konvertierung
- **AfterConversionEvent**: Nach Konvertierung

### Listeners
- **ConversionListener**: Für Custom Processing

## Hints für KI-Agenten

### Wichtig zu verstehen
1. **Tier 5 Modul**: Erweitert Converter Framework
2. **Konvertierungs-fokussiert**: Product → Attribute Option CSV
3. **Observer Pattern**: Für Hooks
4. **Event-Driven**: Für Konvertierungs-Prozesse

## Bekannte Einschränkungen

- **Product-Attribute-Only**: Nur für Product Attributes
- **CSV-Only**: Nur CSV-Format unterstützt

## Zusammenfassung

`import-converter-product-attribute` ist ein **Tier 5 Modul**, das Product CSV zu Attribute Option CSV Konvertierung bietet. Es erweitert den Converter Framework mit spezialisierter Funktionalität.

**Für Agenten:** Verstehe dieses Modul als **Product Attribute Converter** mit Observer und Event-Driven Architektur.
