export interface Moneda {
  id: number;
  nombre: string;
  pais: string;
  abreviacion: string;
  tipo_cambio: number; 
  activo: boolean;
  updated_at?: string;
}
