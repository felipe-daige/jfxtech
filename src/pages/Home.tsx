import { motion } from 'motion/react';
import { Link } from 'react-router-dom';
import { ArrowRight, Star, ShieldCheck, Truck, RotateCcw } from 'lucide-react';
import Carousel from '../components/Carousel';
import ProductCard from '../components/ProductCard';

// Mock Data for Featured Products
const featuredProducts = [
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
  }
];

export default function Home() {
  return (
    <motion.div 
      initial={{ opacity: 0 }} 
      animate={{ opacity: 1 }} 
      exit={{ opacity: 0 }}
      className="w-full bg-white"
    >
      {/* Hero Section - Nano Banana Carousel */}
      <section className="w-full bg-black text-white overflow-hidden relative">
        <Carousel />
      </section>

      {/* Trust Bar (Secretlab style) */}
      <div className="bg-[#111] text-white py-3 border-b border-gray-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-center sm:justify-between items-center gap-6 text-[9px] md:text-xs font-bold tracking-widest uppercase">
            <div className="hidden lg:flex items-center gap-2 flex-shrink-0">
              <Star className="w-3 h-3 md:w-4 md:h-4 text-yellow-500 fill-yellow-500" />
              <span>3 MILHÕES DE USUÁRIOS</span>
            </div>
            <div className="flex items-center gap-2 flex-shrink-0">
              <ShieldCheck className="w-3 h-3 md:w-4 md:h-4" />
              <span>5 ANOS DE GARANTIA</span>
            </div>
            <div className="flex items-center gap-2 flex-shrink-0">
              <Truck className="w-3 h-3 md:w-4 md:h-4" />
              <span>FRETE GRÁTIS</span>
            </div>
            <div className="hidden sm:flex items-center gap-2 flex-shrink-0">
              <RotateCcw className="w-3 h-3 md:w-4 md:h-4" />
              <span>DEVOLUÇÃO EM 45 DIAS</span>
            </div>
          </div>
        </div>
      </div>

      {/* Secretlab Style: Massive Centered Product Highlight */}
      <section className="py-24 md:py-32 bg-white text-center">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.8 }}
          >
            <h2 className="text-4xl md:text-6xl font-black tracking-tight mb-6 uppercase text-gray-900">
              A Experiência Gamer Definitiva.
            </h2>
            <p className="text-lg md:text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
              Projetado do zero para fornecer desempenho inigualável. Descubra o hardware em que campeões e profissionais do mundo todo confiam.
            </p>
            <div className="flex flex-col sm:flex-row justify-center gap-4">
              <Link to="/catalog" className="bg-black text-white px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-800 transition-colors uppercase">
                Comprar Todo o Hardware
              </Link>
              <Link to="/product/1" className="border-2 border-black text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-50 transition-colors uppercase">
                Descubra o Prime-X
              </Link>
            </div>
          </motion.div>
        </div>
        
        {/* Massive Full-Width Image */}
        <motion.div 
          className="w-full mt-20"
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 1, delay: 0.2 }}
        >
          <img 
            src="https://picsum.photos/seed/setup_massive/1920/800" 
            alt="Gaming Setup" 
            className="w-full h-[60vh] object-cover"
            referrerPolicy="no-referrer"
          />
        </motion.div>
      </section>

      {/* Secretlab Style: Split Feature Blocks (Alternating) */}
      <section className="bg-[#f9f9f9] py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          {/* Block 1 */}
          <div className="flex flex-col md:flex-row items-center gap-16 mb-32">
            <div className="w-full md:w-1/2 order-2 md:order-1">
              <motion.div
                initial={{ opacity: 0, x: -50 }}
                whileInView={{ opacity: 1, x: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.6 }}
              >
                <h3 className="text-3xl md:text-4xl font-black tracking-tight mb-4 uppercase">Switches Magnéticos Proprietários.</h3>
                <p className="text-gray-600 text-lg mb-8 leading-relaxed">
                  Experimente o futuro da digitação e dos jogos. Nossos sensores Hall Effect personalizados permitem pontos de atuação ajustáveis de 0,1 mm a 4,0 mm. A tecnologia Rapid Trigger redefine as teclas instantaneamente, dando a você a vantagem no jogo competitivo.
                </p>
                <Link to="/product/2" className="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors border-b-2 border-black pb-1">
                  Explorar Apex TKL Pro <ArrowRight className="w-4 h-4" />
                </Link>
              </motion.div>
            </div>
            <div className="w-full md:w-1/2 order-1 md:order-2">
              <img 
                src="https://picsum.photos/seed/switch_macro/800/800" 
                alt="Magnetic Switches" 
                className="w-full h-auto rounded-lg shadow-xl"
                referrerPolicy="no-referrer"
              />
            </div>
          </div>

          {/* Block 2 */}
          <div className="flex flex-col md:flex-row items-center gap-16">
            <div className="w-full md:w-1/2">
              <img 
                src="https://picsum.photos/seed/mouse_sensor/800/800" 
                alt="Mouse Sensor" 
                className="w-full h-auto rounded-lg shadow-xl"
                referrerPolicy="no-referrer"
              />
            </div>
            <div className="w-full md:w-1/2">
              <motion.div
                initial={{ opacity: 0, x: 50 }}
                whileInView={{ opacity: 1, x: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.6 }}
              >
                <h3 className="text-3xl md:text-4xl font-black tracking-tight mb-4 uppercase">Sem Fio com Zero Latência.</h3>
                <p className="text-gray-600 text-lg mb-8 leading-relaxed">
                  Corte o fio sem comprometer o desempenho. Nossa tecnologia sem fio de 8000 Hz fornece dados 8 vezes mais rápido do que mouses gamers padrão. Combinado com um chassi de magnésio de grau aeroespacial de 49g, é uma extensão da sua mão.
                </p>
                <Link to="/product/1" className="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors border-b-2 border-black pb-1">
                  Explorar Prime-X 8K <ArrowRight className="w-4 h-4" />
                </Link>
              </motion.div>
            </div>
          </div>

        </div>
      </section>

      {/* Secretlab Style: Product Lineup (Clean Grid) */}
      <section className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-black tracking-tight uppercase mb-4">Escolha Sua Arma.</h2>
            <p className="text-gray-600">Hardware premiado para todos os tipos de gamers.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {featuredProducts.map((product, index) => (
              <motion.div
                key={product.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: index * 0.1 }}
              >
                <ProductCard product={product} />
              </motion.div>
            ))}
          </div>
          
          <div className="mt-16 text-center">
            <Link to="/catalog" className="bg-black text-white px-10 py-4 font-bold tracking-widest text-sm hover:bg-gray-800 transition-colors uppercase inline-block">
              Ver Todos os Produtos
            </Link>
          </div>
        </div>
      </section>

      {/* Secretlab Style: Co-branded / Special Editions */}
      <section className="py-24 bg-black text-white relative overflow-hidden">
        <div className="absolute inset-0 z-0">
          <img 
            src="https://picsum.photos/seed/esports_bg/1920/1080" 
            alt="Esports Background" 
            className="w-full h-full object-cover opacity-30 mix-blend-luminosity"
            referrerPolicy="no-referrer"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
        </div>
        
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
          <h2 className="text-3xl md:text-5xl font-black tracking-tight uppercase mb-6">Edições Oficiais de Esports</h2>
          <p className="text-gray-400 text-lg max-w-2xl mx-auto mb-10">
            Represente seus times e jogos favoritos com nossas coleções exclusivas em parceria. Construídas com os mesmos padrões rigorosos da nossa linha principal.
          </p>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="aspect-square bg-white/5 border border-white/10 rounded-lg flex items-center justify-center p-8 hover:bg-white/10 transition-colors cursor-pointer backdrop-blur-sm">
                <img src={`https://picsum.photos/seed/logo${i}/200/200`} alt="Team Logo" className="w-full h-full object-contain opacity-70 hover:opacity-100 transition-opacity grayscale hover:grayscale-0" referrerPolicy="no-referrer" />
              </div>
            ))}
          </div>

          <Link to="/catalog" className="border-2 border-white text-white px-8 py-4 font-bold tracking-widest text-sm hover:bg-white hover:text-black transition-colors uppercase inline-block">
            Comprar Edições Especiais
          </Link>
        </div>
      </section>
    </motion.div>
  );
}
