# AGENTS.md - import-converter-product-attribute

## Zweck & Verantwortung

Product Attribute Converter für Produktattribut-Konvertierung. **Tier 3 Modul**.

**Hauptverantwortung:**
- Data Processing und Konvertierung
- Validation Framework
- Error Handling
- Service Layer Implementation

## Architektur & Design Patterns

### Kern-Klassen
- **Repository**: Persistierungs-Layer
- **Processor**: Service Layer
- **Validator**: Validierungs-Framework
- **Observer**: Lifecycle Hooks

### Verwendete Patterns
- **Observer Pattern**: Für Hooks
- **Repository Pattern**: Datenschicht-Abstraktion
- **Service Layer**: Business Logic
- **Factory Pattern**: Object Creation

## Abhängigkeiten

- **import-***: Verschiedene andere Importer je nach Modul
- **Magento_Framework**: Core Framework

## Wichtige Entry Points

```php
// Repository::create()
Repository::create($row): void
Repository::find($id): Entity
```

## Events & Extension Points

**Observer Hooks** für Lifecycle Integration

## Database Schema

Modul-spezifische Tabellen je nach Verwendung

## Hints für KI-Agenten

### Kritisches Verständnis
1. **Daten-Oriented**: Fokus auf Data Processing
2. **Converter/Serializer**: Transformieren Datenformate
3. **Tier 1-4**: Unterschiedliche Abstraktions-Level
4. **Repository Pattern**: Standard für Persistierung

## Known Limitations

- Format-spezifisch: Abhängig von Input-Format
- Validierungs-Regeln: Streng für Datenkonsistenz

## Zusammenfassung

import-converter-product-attribute: Spezialisiertes Import-Modul für Data Processing und Konvertierung.

**Für Agenten:** Data Processing mit Repository und Service Layer Patterns.