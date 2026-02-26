import { useState } from 'react';
import { motion } from 'motion/react';
import ProductCard from '../components/ProductCard';
import Filters from '../components/Filters';

// Mock Data
const products = [
  {
    id: 1,
    name: "PRIME-X 8K WIRELESS",
    category: "MOUSES",
    price: 149.99,
    image: "https://picsum.photos/seed/mouse1/600/600",
    specs: {
      "SENSOR": "PAW3395",
      "POLLING": "8000Hz",
      "WEIGHT": "49g",
      "SWITCHES": "Optical V3"
    }
  },
  {
    id: 2,
    name: "APEX TKL PRO",
    category: "TECLADOS",
    price: 199.99,
    image: "https://picsum.photos/seed/kb1/600/600",
    specs: {
      "SWITCHES": "Magnetic Hall Effect",
      "ACTUATION": "0.1mm - 4.0mm",
      "POLLING": "4000Hz",
      "KEYCAPS": "PBT Double-shot"
    }
  },
  {
    id: 3,
    name: "VISION 360Hz OLED",
    category: "MONITORES",
    price: 899.99,
    image: "https://picsum.photos/seed/monitor1/600/600",
    specs: {
      "PANEL": "QD-OLED",
      "REFRESH": "360Hz",
      "RESPONSE": "0.03ms",
      "RESOLUTION": "1440p"
    }
  },
  {
    id: 4,
    name: "AERO-FLOW 120",
    category: "REFRIGERAÇÃO",
    price: 34.99,
    image: "https://picsum.photos/seed/fan1/600/600",
    specs: {
      "RPM": "3000 Max",
      "AIRFLOW": "84 CFM",
      "NOISE": "28 dBA",
      "BEARING": "MagLev"
    }
  },
  {
    id: 5,
    name: "SONIC-X HEADSET",
    category: "ÁUDIO",
    price: 249.99,
    image: "https://picsum.photos/seed/headset1/600/600",
    specs: {
      "DRIVERS": "50mm Graphene",
      "FREQ": "10Hz - 40kHz",
      "MIC": "ClearCast Gen 2",
      "BATTERY": "50 Hours"
    }
  },
  {
    id: 6,
    name: "TITAN RTX 5090",
    category: "COMPONENTES",
    price: 1999.99,
    image: "https://picsum.photos/seed/gpu1/600/600",
    specs: {
      "VRAM": "32GB GDDR7",
      "CORES": "24576 CUDA",
      "BOOST": "2.9 GHz",
      "POWER": "600W"
    }
  }
];

export default function Catalog() {
  const [activeCategory, setActiveCategory] = useState('ALL');

  return (
    <motion.div 
      initial={{ opacity: 0 }} 
      animate={{ opacity: 1 }} 
      exit={{ opacity: 0 }}
      className="w-full bg-[var(--color-lab-bg)] min-h-screen"
    >
      {/* Header */}
      <div className="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold tracking-tight mb-2">CATÁLOGO DE HARDWARE</h1>
          <p className="text-gray-500 font-mono text-sm">NAVEGUE PELO NOSSO ARSENAL COMPLETO DE EQUIPAMENTOS DE ALTA PERFORMANCE</p>
        </div>
      </div>

      {/* Marketplace Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex flex-col md:flex-row gap-12">
          
          {/* Sidebar Filters */}
          <aside className="w-full md:w-64 flex-shrink-0">
            <Filters activeCategory={activeCategory} setActiveCategory={setActiveCategory} />
          </aside>

          {/* Product Grid */}
          <div className="flex-1">
            <div className="flex justify-between items-end border-b border-[var(--color-lab-border)] pb-4 mb-8">
              <div>
                <h2 className="text-xl font-bold tracking-tight">TODOS OS PRODUTOS</h2>
                <p className="text-sm text-gray-500 font-mono mt-1">MOSTRANDO {products.length} RESULTADOS</p>
              </div>
              <div className="flex items-center gap-2 text-sm font-mono text-gray-500">
                <span>ORDENAR POR:</span>
                <select className="bg-transparent border-none outline-none font-bold text-black cursor-pointer">
                  <option>DESEMPENHO</option>
                  <option>PREÇO (MENOR-MAIOR)</option>
                  <option>PREÇO (MAIOR-MENOR)</option>
                  <option>MAIS RECENTES</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {products.map((product, index) => (
                <motion.div
                  key={product.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                >
                  <ProductCard product={product} />
                </motion.div>
              ))}
            </div>
          </div>
        </div>
      </section>
    </motion.div>
  );
}
