# Profiles

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md)

Exporta las calificaciones en diferentes formatos (ods, excel, csv) desde la misma página, y organiza perfiles para guardar las selecciones y opciones.

Es un fork de ```gradeexport_ods``` (plugin que viene por defecto en Moodle) que permite al usuario trabajar con perfiles para guardar sus selecciones cuando exporta calificaciones.

Cuando un usuario exporta calificaciones, sus selecciones de los ítems y opciones se pierden. Los plugins estándar muestran todos los ítems seleccionados y una oferta por defecto de opciones cada vez que se ingresa para exportar calificaciones.

Esto puede ser molesto cuando hay muchas actividades en un curso, y para evitar eso ```profiles``` provee la capacidad de guardar las selecciones del usuario en cada curso.

También permite al usuario elegir el formato de archivo para exportar, evitando tener que acceder a diferentes plugins para cada formato.

Los archivos que se exportan son producidos por funciones incluidas por defecto en Moodle. Esto significa que utilizando este plugin se obtienen las mismas salidas que utilizando los plugins de exportación estándar.

## Funcionalidad

- guardar/cargar/borrar perfiles que almacenan las selecciones de ítems de calificación y opciones para exportar.
- muestra ```new item``` cuando un nuevo ítem de calificación es añadido luego de haber guardado un perfil.
- soporta exportar como ods (Open Document Spreadsheet), Excel y archivo de texto plano.

## Instalar subiendo un archivo ZIP

1. Ingresar al sitio Moodle como administrador e ir a _Administración del sitio >
   Extensiones > Instalar complementos_.
2. Cargar el archivo ZIP con el código del plugin.
3. Revisar el reporte de validación y finalizar la instalación.

## Instalación manual

Este plugin también puede ser instalado copiando los contenidos de este directorio en

    {directorio/de/tu/moodle}/grade/export/profiles

Luego, ingresar al sitio Moodle como administrador para completar la instalación.

Alternativamente, se puede ejecutar

    $ php admin/cli/upgrade.php

para completar la instalación desde un terminal.

## Licencia ##

2023 Edgardo Palazzo <epalazzo@fra.utn.edu.ar>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
