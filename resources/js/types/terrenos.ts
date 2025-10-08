export interface Terreno {
  id: number;
  idproyecto: number;
  idcategoria: number;
  ubicacion: string;
  categoria: string;
  superficie: string;
  precio_venta: number;
  estado: number;
  cuota_inicial: number;
  cuota_mensual: number;
  condicion: boolean;
  proyecto:{
    nombre: string;
    descripcion: string;
    estado: number
  }
  categorias_terrenos:{
    id: number,
    nombre: string;
  }
}
